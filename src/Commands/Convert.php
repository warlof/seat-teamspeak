<?php
/**
 * This file is part of SeAT Teamspeak Connector.
 *
 * Copyright (C) 2019  Warlof Tutsimo <loic.leuilliot@gmail.com>
 *
 * SeAT Teamspeak Connector  is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * SeAT Teamspeak Connector is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Warlof\Seat\Connector\Drivers\Teamspeak\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Seat\Eveapi\Models\Alliances\Alliance;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Eveapi\Models\Corporation\CorporationTitle;
use Seat\Web\Models\Acl\Role;
use Seat\Web\Models\Group;
use Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakClient;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException;
use Warlof\Seat\Connector\Models\Set;
use Warlof\Seat\Connector\Models\User;

/**
 * Class Convert.
 *
 * @package Warlof\Seat\Connector\Drivers\Teamspeak\Commands
 */
class Convert extends Command
{
    /**
     * @var string
     */
    protected $signature = 'seat-connector:convert:teamspeak';

    /**
     * @var string
     */
    protected $description = 'Process data conversion from Teamspeak 3.x generation to 4.x';

    /**
     * @var \Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakClient
     */
    private $client;

    /**
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function handle()
    {
        $this->line('This Wizard will guide you in the process of data conversion between Teamspeak Connector version prior to 4.0 and SeAT Connector');
        $this->line(sprintf('You can run it at any time using "php artisan %s"', $this->signature));
        $this->line('At the end of data conversion process, existing tables will not be removed. In case you want to erase them, you have to do it manually.');
        $this->line(' - teamspeak_group_alliances');
        $this->line(' - teamspeak_group_corporations');
        $this->line(' - teamspeak_group_public');
        $this->line(' - teamspeak_group_roles');
        $this->line(' - teamspeak_group_titles');
        $this->line(' - teamspeak_group_users');
        $this->line(' - teamspeak_groups');
        $this->line(' - teamspeak_users');
        $this->line(' - teamspeak_logs');

        $this->line('');
        $this->line('We will have to ask you for a few information regarding your Teamspeak Server - then, everything will run automatically');
        $this->line('');

        $server_host = $this->ask('What is either the IP address or domain name where your Teamspeak instance is ?', 'localhost');
        $server_port = intval($this->ask('What is the client port from your Teamspeak instance ?', 9987));
        $query_port = intval($this->ask('What is the query port from your Teamspeak server ?', 10011));
        $query_username = $this->ask('What is the server query username from your Teamspeak server ?', 'serveradmin');
        $query_password = $this->secret('What is the server query password from your Teamspeak server ?');

        $this->client = $this->setup($server_host, $server_port, $query_port, $query_username, $query_password);

        if ($this->requireConversion()) {
            $this->info('It appears you were running a previous version of warlof/seat-teamspeak.');
            if (! $this->confirm('Do you want to convert existing data to the new SeAT Connector layout ?', true)) {
                $this->line(
                    sprintf('We did not convert anything. You can always run this wizard using "php artisan %s"', $this->signature));
            }

            $this->convert();

            $this->info('Process has been completed. Please review upper warning or errors to be sure everything is going well.');
        }
    }

    /**
     * @param string $server_host
     * @param int $server_port
     * @param int $query_port
     * @param string $query_username
     * @param string $query_password
     * @return \Warlof\Seat\Connector\Drivers\IClient|\Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakClient
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    private function setup(string $server_host, int $server_port, int $query_port, string $query_username, string $query_password)
    {
        if (in_array(null, [$server_host, $server_port, $query_port, $query_username, $query_password]))
            $this->error('You must provide a value for all parameters.');

        if (! is_string($server_host))
            $this->error('Server Host must be a valid domain or IP address');

        if ($server_port == 0 || $server_port < 1 || $server_port > 65535)
            $this->error('Server Port must be a valid port number');

        if ($query_port == 0 || $query_port < 1 || $query_port > 65535)
            $this->error('Query Port must be a valid port number');

        if (empty($query_username) || ! is_string($query_username))
            $this->error('Query Username must be a valid string');

        if (empty($query_password) || ! is_string($query_password))
            $this->error('Query Password must be a valid string');

        $settings = [
            'server_host'    => $server_host,
            'server_port'    => (int) $server_port,
            'query_port'     => (int) $query_port,
            'query_username' => $query_username,
            'query_password' => $query_password,
        ];

        setting(['seat-connector.drivers.teamspeak', (object) $settings], true);

        // attempt to connect to Teamspeak and pull servers groups
        try {
            $client = TeamspeakClient::getInstance();
            $client->getSets();

            return $client;
        } catch (TeamspeakException $e) {
            $this->error('Unable to connect to the Teamspeak instance');
            throw $e;
        }
    }

    /**
     * @return bool
     */
    private function requireConversion(): bool
    {
        $deprecated_tables = [
            'teamspeak_users',
            'teamspeak_groups',
            'teamspeak_group_public',
            'teamspeak_group_users',
            'teamspeak_group_alliances',
            'teamspeak_group_corporations',
            'teamspeak_group_titles',
            'teamspeak_group_roles',
        ];

        foreach ($deprecated_tables as $table)
            if (! Schema::hasTable($table))
                return false;

        return true;
    }

    private function convert()
    {
        $progress = $this->output->createProgressBar(9);

        $this->flushConnectorData();
        $progress->advance();

        $this->convertUsers();
        $progress->advance();

        $this->convertSets();
        $progress->advance();

        $this->convertPublicSets();
        $progress->advance();

        $this->convertUserSets();
        $progress->advance();

        $this->convertRoleSets();
        $progress->advance();

        $this->convertAllianceSets();
        $progress->advance();

        $this->convertCorporationSets();
        $progress->advance();

        $this->convertTitleSets();
        $progress->advance();

        $progress->finish();
    }

    private function flushConnectorData()
    {
        User::where('connector_type', 'teamspeak')->delete();
        Set::where('connector_type', 'teamspeak')->delete();

        $this->info('SeAT Connector has been purged from teamspeak data.');
    }

    private function convertUsers()
    {
        $users = DB::table('teamspeak_users')->get();

        $this->info(
            sprintf('Preparing to convert schema data for Teamspeak Users Accounts: %d', $users->count()));

        foreach ($users as $user) {

            if (! is_null(User::where('connector_type', 'teamspeak')->where('group_id', $user->group_id)))
                continue;

            try {
                $teamspeak_user = $this->client->sendCall('clientGetNameFromUid', [$user->teamspeak_id]);

                $connector_user = new User();
                $connector_user->connector_type = 'teamspeak';
                $connector_user->connector_id   = $teamspeak_user['data']['cldbid'];
                $connector_user->connector_name = $teamspeak_user['data']['name'];
                $connector_user->unique_id      = $user->teamspeak_id;
                $connector_user->group_id       = $user->group_id;
                $connector_user->created_at     = $user->created_at;
                $connector_user->updated_at     = $user->updated_at;
                $connector_user->save();

                $this->line(
                    sprintf('Teamspeak User Account uid:%s, group_id:%s has been successfully converted.',
                        $user->teamspeak_id, $user->group_id));
            } catch (TeamspeakException $e) {
                $this->warn(
                    sprintf('Impossible to convert Teamspeak User Account uid:%s, group_id:%s',
                        $user->teamspeak_id, $user->group_id));
                $this->warn($e->getMessage());
            }
        }

        $this->info('Teamspeak User Accounts has been converted.');
    }

    private function convertSets()
    {
        $sets = DB::table('teamspeak_groups')->get();

        $this->info(
            sprintf('Preparing to convert schema data for Teamspeak Sets: %d', $sets->count()));

        foreach ($sets as $set) {
            $connector_set = new Set();
            $connector_set->connector_type = 'teamspeak';
            $connector_set->connector_id   = $set->id;
            $connector_set->name           = $set->name;
            $connector_set->save();

            $this->line(
                sprintf('Teamspeak Set sgid:%s has been successfully converted.',
                    $set->id));
        }

        $this->info('Teamspeak Sets has been converted.');
    }

    private function convertPublicSets()
    {
        $policies = DB::table('teamspeak_group_public')->get();

        $this->info(
            sprintf('Preparing to convert schema data for Teamspeak Public Policies: %d.', $policies->count()));

        foreach ($policies as $policy) {

            $connector_set = Set::where('connector_type', 'teamspeak')
                ->where('connector_id', $policy->teamspeak_sgid)
                ->first();

            if (is_null($connector_set)) {
                $this->warn(
                    sprintf('Unable to retrieve Teamspeak Set with ID: %s.', $policy->teamspeak_sgid));

                continue;
            }

            $connector_set->is_public = true;
            $connector_set->save();

            $this->line(
                sprintf('Teamspeak Public Policy for Teamspeak Set %s has been successfully converted.',
                    $policy->teamspeak_sgid));
        }

        $this->info('Teamspeak Public Policies has been converted.');
    }

    private function convertUserSets()
    {
        $policies = DB::table('teamspeak_group_users')->get();

        $this->info(
            sprintf('Preparing to convert schema data for Teamspeak User Policies: %d.', $policies->count()));

        foreach ($policies as $policy) {

            $connector_set = Set::where('connector_type', 'teamspeak')
                ->where('connector_id', $policy->teamspeak_sgid)
                ->first();

            if (is_null($connector_set)) {
                $this->warn(
                    sprintf('Unable to retrieve Teamspeak Set with ID: %s.', $policy->teamspeak_sgid));

                continue;
            }

            DB::table('seat_connector_set_entity')->insert([
                'set_id'      => $connector_set->id,
                'entity_type' => Group::class,
                'entity_id'   => $policy->group_id,
            ]);

            $this->line(
                sprintf('Teamspeak User Policy for Teamspeak Set %s - User %s has been successfully converted.',
                    $connector_set->id, $policy->group_id));
        }

        $this->info('Teamspeak User Policies has been converted.');
    }

    private function convertRoleSets()
    {
        $policies = DB::table('teamspeak_group_roles')->get();

        $this->info(
            sprintf('Preparing to convert schema data for Teamspeak Role Policies: %d.', $policies->count()));

        foreach ($policies as $policy) {

            $connector_set = Set::where('connector_type', 'teamspeak')
                ->where('connector_id', $policy->teamspeak_sgid)
                ->first();

            if (is_null($connector_set)) {
                $this->warn(
                    sprintf('Unable to retrieve Teamspeak Set with ID: %s.', $policy->teamspeak_sgid));

                continue;
            }

            DB::table('seat_connector_set_entity')->insert([
                'set_id'      => $connector_set->id,
                'entity_type' => Role::class,
                'entity_id'   => $policy->role_id,
            ]);

            $this->line(
                sprintf('Teamspeak User Policy for Teamspeak Set %s - Role %s has been successfully converted.',
                    $connector_set->id, $policy->role_id));
        }

        $this->info('Teamspeak Role Policies has been converted.');
    }

    private function convertAllianceSets()
    {
        $policies = DB::table('teamspeak_group_alliances')->get();

        $this->info(
            sprintf('Preparing to convert schema data for Teamspeak Alliance Policies: %d.', $policies->count()));

        foreach ($policies as $policy) {

            $connector_set = Set::where('connector_type', 'teamspeak')
                ->where('connector_id', $policy->teamspeak_sgid)
                ->first();

            if (is_null($connector_set)) {
                $this->warn(
                    sprintf('Unable to retrieve Teamspeak Set with ID: %s.', $policy->teamspeak_sgid));

                continue;
            }

            DB::table('seat_connector_set_entity')->insert([
                'set_id'      => $connector_set->id,
                'entity_type' => Alliance::class,
                'entity_id'   => $policy->alliance_id,
            ]);

            $this->line(
                sprintf('Teamspeak Alliance Policy for Teamspeak Set %s - Alliance %s has been successfully converted.',
                    $connector_set->id, $policy->alliance_id));
        }

        $this->info('Teamspeak Alliance Policies has been converted.');
    }

    private function convertCorporationSets()
    {
        $policies = DB::table('teamspeak_group_corporations')->get();

        $this->info(
            sprintf('Preparing to convert schema data for Teamspeak Corporation Policies: %d.', $policies->count()));

        foreach ($policies as $policy) {

            $connector_set = Set::where('connector_type', 'teamspeak')
                ->where('connector_id', $policy->teamspeak_sgid)
                ->first();

            if (is_null($connector_set)) {
                $this->warn(
                    sprintf('Unable to retrieve Teamspeak Set with ID: %s.', $policy->teamspeak_sgid));

                continue;
            }

            DB::table('seat_connector_set_entity')->insert([
                'set_id'      => $connector_set->id,
                'entity_type' => CorporationInfo::class,
                'entity_id'   => $policy->corporation_id,
            ]);

            $this->line(
                sprintf('Teamspeak Corporation Policy for Teamspeak Set %s - Corporation %s has been successfully converted.',
                    $connector_set->id, $policy->corporation_id));
        }

        $this->info('Teamspeak Corporation Policies has been converted.');
    }

    private function convertTitleSets()
    {
        $policies = DB::table('teamspeak_group_titles')->get();

        $this->info(
            sprintf('Preparing to convert schema data for Teamspeak Title Policies: %d.', $policies->count()));

        foreach ($policies as $policy) {

            $connector_set = Set::where('connector_type', 'teamspeak')
                ->where('connector_id', $policy->teamspeak_sgid)
                ->first();

            $title = CorporationTitle::where('corporation_id', $policy->corporation_id)
                ->where('title_id', $policy->title_id)
                ->first();

            if (is_null($connector_set)) {
                $this->warn(
                    sprintf('Unable to retrieve Teamspeak Set with ID: %s.', $policy->teamspeak_sgid));

                continue;
            }

            if (is_null($title)) {
                $this->warn(
                    sprintf('Unable to retrieve Corporation Title with title ID: %s, Corporation ID: %s.',
                        $policy->title_id, $policy->corporation_id));

                continue;
            }

            DB::table('seat_connector_set_entity')->insert([
                'set_id'      => $connector_set->id,
                'entity_type' => CorporationTitle::class,
                'entity_id'   => $title->id,
            ]);

            $this->line(
                sprintf('Teamspeak Title Policy for Teamspeak Set %s - Corporation %s - Title %s has been successfully converted.',
                    $connector_set->id, $policy->corporation_id, $policy->title_id));
        }

        $this->info('Teamspeak Title Policies has been converted.');
    }
}
