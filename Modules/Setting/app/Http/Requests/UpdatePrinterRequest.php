<?php

namespace Modules\Setting\Http\Requests;

class UpdatePrinterRequest extends StorePrinterRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
