<?php

namespace Secomapp\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

class AddAppRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'shop' => 'required|regex:/^[0-9a-z\.\-]+\\.myshopify\\.com$/',
        ];
    }

    /**
     * Get data to be validated from the request.
     *
     * @return array
     */
    protected function validationData()
    {
        $shop = $this->input('shop');
        if (!isValidDomain($shop)) {
            $this->merge(['shop' => domainFromShopName($shop)]);
        }

        return parent::validationData();
    }
}
