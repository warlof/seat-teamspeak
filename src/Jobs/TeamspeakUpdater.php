<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 25/06/2016
 * Time: 20:30
 */

namespace Seat\Warlof\Teamspeak\Jobs;


use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seat\Eveapi\Helpers\JobPayloadContainer;
use Seat\Eveapi\Models\JobTracking;
use Seat\Services\Helpers\AnalyticsContainer;
use Seat\Services\Jobs\Analytics;
use Seat\Warlof\Teamspeak\Jobs\Workers\TeamspeakAssKicker;
use Seat\Warlof\Teamspeak\Jobs\Workers\TeamspeakReceptionist;

class TeamspeakUpdater implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, Queueable;

    /**
     * The JobPayloadContainer instance containing extra payload information
     *
     * @var JobPayloadContainer
     */
    protected $jobPayload;

    /**
     * The JobTracker instance
     *
     * @var JobTracking
     */
    protected $jobTracker;

    /**
     * Teamspeak bot workers
     *
     * @var array
     */
    private $workers;

    /**
     * Create a new job instance.
     *
     * @param JobPayloadContainer $jobPayload
     */
    public function __construct(JobPayloadContainer $jobPayload)
    {
        $this->jobPayload = $jobPayload;

        // Setup workers
        $this->workers = [
            TeamspeakReceptionist::class,
            TeamspeakAssKicker::class
        ];
    }

    /**
     * Force defining the handle method for the Job worker to call.
     */
    public function handle()
    {
        if (!$this->trackOrDismiss()) {
            return;
        }

        // iterate over all Teamspeak bot workers
        foreach ($this->workers as $worker) {

            try {
                // update job status
                $this->updateJobStatus([
                    'output' => 'Processing: ' . class_basename($worker)
                ]);

                // fire new worker
                (new $worker)->setUser($this->jobPayload->user)->call();

            } catch (Exception $e) {
                $this->reportJobError($e);
                return;
            }

        }

        // Mark the Job as complete
        $this->updateJobStatus([
            'status' => 'Done',
            'output' => null
        ]);
    }

    /**
     * Handle an exception that can be thrown by a job.
     *
     * This is the failed method that Laravel itself will call
     * when a jobs `handle` method throws any uncaught exception.
     *
     * @param Exception $exception
     * @author Leon Jacobs
     */
    public function failed(Exception $exception)
    {
        logger()->error(
            'A worker error occured. The exception thrown was ' .
            $exception->getMessage() . ' in file ' . $exception->getFile() .
            ' on line ' . $exception->getLine()
        );

        $jobTracker = JobTracking::where('owner_id', $this->jobPayload->owner_id)
            ->where('api', $this->jobPayload->api)
            ->where('scope', $this->jobPayload->scope)
            ->where('status', '<>', 'Error')
            ->first();


        if ($jobTracker) {
            // Prepare some useful information about the error.
            $output = 'Last Updater: ' . $jobTracker->output . PHP_EOL;
            $output .= PHP_EOL;
            $output .= 'Exception       : ' . get_class($exception) . PHP_EOL;
            $output .= 'Error Code      : ' . $exception->getCode() . PHP_EOL;
            $output .= 'Error Message   : ' . $exception->getMessage() . PHP_EOL;
            $output .= 'File            : ' . $exception->getFile() . PHP_EOL;
            $output .= 'Line            : ' . $exception->getLine() . PHP_EOL;
            $output .= PHP_EOL;
            $output .= 'Traceback: ' . PHP_EOL;
            $output .= $exception->getTraceAsString() . PHP_EOL;

            $this->updateJobStatus([
                'status' => 'Error',
                'output' => $output
            ]);
        }

        // Analytics. Report only the Exception class and message.
        dispatch((new Analytics((new AnalyticsContainer)
            ->set('type', 'exception')
            ->set('exd', get_class($exception) . ':' . $exception->getMessage())
            ->set('exf', 1)))
            ->onQueue('medium'));
    }

    /**
     * Checks the Job tracking table if the current job has a tracking entry.
     * If not, the job is just deleted.
     *
     * @return bool
     * @author Leon Jacobs
     */
    private function trackOrDismiss()
    {
        // Retrieve job tracking record we added when queuing the job
        $this->jobTracker = JobTracking::find($this->job->getJobId());

        // If no tracking record is found, just put the job back in the queue after a few seconds.
        // It could be that the job to add it has not finished yet
        if (!$this->jobTracker) {

            // Check that we have not come by this logic for like the 10th time now
            if ($this->attempts() < 10) {
                // Add the job back into the queue and wait for 2 seconds before releasing it.
                $this->release(2);

                return false;
            }

            // Remove yourself from the queue
            logger()->error(
                'Error finding a JobTracker for job ' . $this->job->getJobId()
            );

            $this->delete();

            return false;
        }

        return true;
    }

    /**
     * @param Exception $exception
     * @author Leon Jacobs
     */
    private function reportJobError(Exception $exception)
    {
        // Write an entry to the log file
        logger()->error(
            $this->jobTracker->api . '/' . $this->jobTracker->scope . ' for ' .
            $this->jobTracker->owner_id . ' failed with ' . get_class($exception) .
            ': ' . $exception->getMessage() . '. See the job tracker for more information.'
        );

        // Prepare some useful information about the error.
        $output  = 'Last Updater: ' . $this->jobTracker->output . PHP_EOL;
        $output .= PHP_EOL;
        $output .= 'Exception       : ' . get_class($exception) . PHP_EOL;
        $output .= 'Error Code      : ' . $exception->getCode() . PHP_EOL;
        $output .= 'Error Message   : ' . $exception->getMessage() . PHP_EOL;
        $output .= 'File            : ' . $exception->getFile() . PHP_EOL;
        $output .= 'Line            : ' . $exception->getLine() . PHP_EOL;
        $output .= PHP_EOL;
        $output .= 'Traceback: ' . PHP_EOL;
        $output .= $exception->getTraceAsString() . PHP_EOL;

        $this->updateJobStatus([
            'status' => 'Error',
            'output' => $output
        ]);

        // Analytics. Report only the Exception class and message.
        dispatch((new Analytics((new AnalyticsContainer)
            ->set('type', 'exception')
            ->set('exd', get_class($exception) . ':' . $exception->getMessage())
            ->set('exf', 1)))
            ->onQueue('medium'));

        return;
    }

    /**
     * Update the JobTracker with a new status.
     *
     * @param array $data
     * @author Leon Jacobs
     */
    private function updateJobStatus(array $data)
    {
        $this->jobTracker->fill($data);
        $this->jobTracker->save();
    }
}
