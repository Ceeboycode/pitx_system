<?php

namespace App\Http\Requests\Company;

use App\Models\CompanyProfileChangeRequest;
use Illuminate\Foundation\Http\FormRequest;

class ApproveCompanyProfileChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CompanyProfileChangeRequest|null $changeRequest */
        $changeRequest = $this->route('changeRequest');
        $user = $this->user();

        return (bool) $changeRequest
            && $user !== null
            && $user->company_id === null
            && $changeRequest->isPending();
    }

    public function rules(): array
    {
        return [];
    }
}
