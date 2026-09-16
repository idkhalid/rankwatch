<?php

namespace App\Http\Requests;

use App\Services\CrawlUrlValidator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $limit = $this->user()->planLimit('projects');

                if ($this->user()->projects()->count() >= $limit) {
                    $plan = ucfirst($this->user()->plan);
                    $website = $limit === 1 ? 'website' : 'websites';
                    $validator->errors()->add('plan', "Your {$plan} plan supports {$limit} {$website}. Upgrade to Pro to monitor additional websites.");
                }
            },
        ];
    }
}
