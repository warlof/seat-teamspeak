<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 20/06/2016
 * Time: 22:12
 */

namespace Seat\Warlof\Teamspeak\Validation;

use Illuminate\Foundation\Http\FormRequest;

class ValidateConfiguration extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'teamspeak-configuration-username' => 'required|string',
            'teamspeak-configuration-password' => 'required|string',
            'teamspeak-configuration-hostname' => 'required|string',
            'teamspeak-configuration-query' => 'required|integer',
            'teamspeak-configuration-port' => 'required|integer'
        ];
    }
}
