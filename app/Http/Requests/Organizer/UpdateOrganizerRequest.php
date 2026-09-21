<?php

namespace App\Http\Requests\Organizer;

use App\Enums\OrganizerType;
use Illuminate\Validation\Rule;

class UpdateOrganizerRequest extends StoreOrganizerRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'type' => ['sometimes', 'required', Rule::enum(OrganizerType::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        // status tidak dipaksa ACTIVE saat update
    }

    public function validatedWithFlags(): array
    {
        $data = $this->validated();

        if (isset($data['type'])) {
            $data['is_ptpn_group'] = OrganizerType::from($data['type'])->isPtpnGroup();
        }

        return $data;
    }
}
