<?php

namespace App\Http\Requests;

use App\Services\CrawlUrlValidator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'url' => [
                'required',
                'url',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! app(CrawlUrlValidator::class)->isSafe((string) $value)) {
                        $fail('Enter a public HTTP or HTTPS website URL.');
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
