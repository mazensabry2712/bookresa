<?php

namespace App\Http\Requests\Customer;

class UpdateCustomerRequest extends StoreCustomerRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customers.update') ?? false;
    }
}
