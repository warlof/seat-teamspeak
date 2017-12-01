<?PHP

/**
 * User: Denngarr B'tarn <ed.stafford@gmail.com>
 * Date: 2017/11/22
 * Time: 19:00
 */

namespace Seat\Warlof\Teamspeak\Http\Controllers;


use Illuminate\Support\Facades\Redis;
use Seat\Eveapi\Models\Corporation\CorporationSheet;
use Seat\Eveapi\Models\Corporation\Title;
use Seat\Eveapi\Models\Eve\AllianceList;
use Seat\Web\Http\Controllers\Controller;
use Seat\Web\Models\Acl\Role;
use Seat\Web\Models\User;

class TeamspeakJsonController extends Controller
{
    public function getJsonTitle()
    {
        $corporationId = request()->input('corporation_id');

        if (!empty($corporationId)) {
            $titles = Title::where('corporationID', $corporationId)->select('titleID', 'titleName')
                ->get();

            return response()->json($titles->map(
                function($item){
                    return [
                        'titleID' => $item->titleID,
                        'titleName' => strip_tags($item->titleName)
                    ];
                })
            );
        }

        return response()->json([]);
    }
}
