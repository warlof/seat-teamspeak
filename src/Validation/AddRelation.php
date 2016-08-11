<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 20/06/2016
 * Time: 22:12
 */

namespace Seat\Warlof\Teamspeak\Validation;

use App\Http\Requests\Request;

class AddRelation extends Request
{
    public function rules()
    {
        return [
            'teamspeak-type' => 'required|string',
            'teamspeak-user-id' => 'string',
            'teamspeak-role-id' => 'string',
            'teamspeak-corporation-id' => 'string',
            'teamspeak-alliance-id' => 'string',
            'teamspeak-channel-id' => 'required|string',
            'teamspeak-enabled' => 'boolean'
        ];
    }
}