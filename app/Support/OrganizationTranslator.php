<?php

namespace App\Support;

use Illuminate\Translation\Translator;

/**
 * Translator that speaks the organization's language: for a signed-in user of a company every
 * school phrase known to Terms (classroom, student, teacher, ...) becomes its company wording
 * (department, employee, trainer, ...) - in __(), trans_choice(), validation and Blade alike.
 */
class OrganizationTranslator extends Translator
{
    private bool $resolvingContext = false;

    /**
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|array<mixed>
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        if (is_string($key) && ! $this->resolvingContext && Terms::hasCompanyPhrase($key)) {
            // Resolving the signed-in user must not translate through this path again.
            $this->resolvingContext = true;

            try {
                $key = Terms::isCompany() ? Terms::companyPhrase($key) : $key;
            } finally {
                $this->resolvingContext = false;
            }
        }

        return parent::get($key, $replace, $locale, $fallback);
    }
}
