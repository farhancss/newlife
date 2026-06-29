<?php

namespace App\Services\Squarespace;

use Illuminate\Support\Carbon;

/**
 * Normalises a Squarespace Commerce order into the data the student-onboarding
 * flow needs. A single `order.create` webhook carries everything: the customer
 * email + id, the billing address, the purchased package (line items), and the
 * checkout form answers (`formSubmission`) that pre-fill the onboarding steps.
 *
 * Form/customisation answers are matched by fuzzy, case-insensitive label so the
 * mapping survives small wording changes on the storefront form.
 */
class SquarespaceOrderMapper
{
    public function __construct(
        private readonly PackageTierMapper $tierMapper,
    ) {
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    public function map(array $order): array
    {
        $form = $this->entries($order['formSubmission'] ?? []);
        $lineItems = is_array($order['lineItems'] ?? null) ? $order['lineItems'] : [];
        $customizations = $this->entries($this->firstCustomizations($lineItems));

        $billing = is_array($order['billingAddress'] ?? null) ? $order['billingAddress'] : [];
        $shipping = is_array($order['shippingAddress'] ?? null) ? $order['shippingAddress'] : [];
        $address = $shipping !== [] ? $shipping : $billing;

        $email = strtolower(trim((string) (
            $order['customerEmail']
            ?? $this->value($form, ['student', 'email'])
            ?? $this->value($customizations, ['email'])
            ?? ($billing['email'] ?? '')
        )));

        [$firstName, $lastName] = $this->resolveName($billing, $form, $customizations);

        $studentPhone = $this->clean(
            $this->value($form, ['student', 'phone'])
            ?? $this->value($customizations, ['phone'])
            ?? ($address['phone'] ?? null)
        );

        $university = $this->value($form, ['university']);

        return [
            'email' => $email,
            'contact_id' => (string) ($order['customerId'] ?? $order['customer']['id'] ?? ''),
            'order_id' => (string) ($order['id'] ?? $order['orderId'] ?? ''),
            'student' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $studentPhone,
                'school' => $university,
                'incoming_year' => $this->incomingYear($order),
            ],
            'parent' => [
                'name' => $this->value($form, ['parent', 'name']),
                'email' => $this->lowerOrNull($this->value($form, ['parent', 'email'])),
                'phone' => $this->clean($this->value($form, ['parent', 'phone'])),
                'relationship' => 'Parent/Guardian',
            ],
            'home_address' => [
                'line1' => $this->clean($address['address1'] ?? $address['line1'] ?? null),
                'line2' => $this->clean($address['address2'] ?? $address['line2'] ?? null),
                'city' => $this->clean($address['city'] ?? null),
                'region' => $this->clean($address['state'] ?? $address['region'] ?? null),
                'postal_code' => $this->clean($address['postalCode'] ?? $address['zip'] ?? null),
                'country_code' => $this->clean($address['countryCode'] ?? $address['country'] ?? null),
                'phone' => $this->clean($address['phone'] ?? null) ?? $studentPhone,
            ],
            'housing' => [
                'university' => $university,
                'residence_hall' => $this->value($form, ['housing']),
                'move_in_classification' => $this->value($form, ['classification']),
            ],
            'agreements' => $this->agreements($form),
            'tier' => $this->tierMapper->mapFromLineItems($lineItems),
            'grand_total_cents' => $this->cents($order['grandTotal'] ?? null),
        ];
    }

    /**
     * Convert a Squarespace money object ({ value, currency }) or numeric value
     * into integer cents.
     */
    private function cents(mixed $money): ?int
    {
        if ($money === null) {
            return null;
        }

        $value = is_array($money) ? ($money['value'] ?? null) : $money;

        if ($value === null || $value === '') {
            return null;
        }

        return (int) round(((float) $value) * 100);
    }

    /**
     * Normalise a list of {label, value} objects into searchable entries.
     *
     * @param  mixed  $raw
     * @return array<int, array{label: string, value: string|null}>
     */
    private function entries(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $entries = [];

        foreach ($raw as $entry) {
            if (! is_array($entry) || ! isset($entry['label'])) {
                continue;
            }

            $entries[] = [
                'label' => strtolower(trim((string) $entry['label'])),
                'value' => $this->clean($entry['value'] ?? null),
            ];
        }

        return $entries;
    }

    /**
     * First non-empty value whose label contains every needle (and no excluded
     * term).
     *
     * @param  array<int, array{label: string, value: string|null}>  $entries
     * @param  list<string>  $needles
     * @param  list<string>  $exclude
     */
    private function value(array $entries, array $needles, array $exclude = []): ?string
    {
        foreach ($entries as $entry) {
            $label = $entry['label'];

            foreach ($needles as $needle) {
                if (! str_contains($label, $needle)) {
                    continue 2;
                }
            }

            foreach ($exclude as $term) {
                if (str_contains($label, $term)) {
                    continue 2;
                }
            }

            if ($entry['value'] !== null && $entry['value'] !== '') {
                return $entry['value'];
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $lineItems
     * @return array<int, mixed>
     */
    private function firstCustomizations(array $lineItems): array
    {
        foreach ($lineItems as $item) {
            if (is_array($item) && is_array($item['customizations'] ?? null)) {
                return $item['customizations'];
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $billing
     * @param  array<int, array{label: string, value: string|null}>  $form
     * @param  array<int, array{label: string, value: string|null}>  $customizations
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveName(array $billing, array $form, array $customizations): array
    {
        $first = $this->clean($billing['firstName'] ?? null);
        $last = $this->clean($billing['lastName'] ?? null);

        if ($first !== null && $last !== null) {
            return [$first, $last];
        }

        $full = $this->value($form, ['student', 'name'])
            ?? $this->value($customizations, ['name'])
            ?? trim(((string) ($billing['firstName'] ?? '')) . ' ' . ((string) ($billing['lastName'] ?? '')));

        [$splitFirst, $splitLast] = $this->splitName((string) $full);

        return [$first ?? $splitFirst, $last ?? $splitLast];
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function splitName(string $full): array
    {
        $parts = preg_split('/\s+/', trim($full)) ?: [];
        $parts = array_values(array_filter($parts, static fn ($p) => $p !== ''));

        if ($parts === []) {
            return [null, null];
        }

        $first = array_shift($parts);

        return [$first, $parts === [] ? null : implode(' ', $parts)];
    }

    /**
     * @param  array<string, mixed>  $order
     */
    private function incomingYear(array $order): ?string
    {
        $createdOn = $order['createdOn'] ?? null;

        if (! is_string($createdOn) || $createdOn === '') {
            return null;
        }

        try {
            return (string) Carbon::parse($createdOn)->year;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Map every agreement/acknowledgement answer (label => value) for auditing.
     *
     * @param  array<int, array{label: string, value: string|null}>  $form
     * @return array<string, string|null>
     */
    private function agreements(array $form): array
    {
        $agreements = [];

        foreach ($form as $entry) {
            if (str_contains($entry['label'], 'i agree') || str_contains($entry['label'], 'i understand')) {
                $agreements[$entry['label']] = $entry['value'];
            }
        }

        return $agreements;
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return $value === null ? null : (string) $value;
        }

        // Drop leading bullet/markdown markers ("• ", "- ", "* ") and trim.
        $cleaned = trim(preg_replace('/^[\x{2022}\x{00B7}\-\*\s]+/u', '', $value) ?? $value);

        return $cleaned === '' ? null : $cleaned;
    }

    private function lowerOrNull(?string $value): ?string
    {
        $value = $this->clean($value);

        return $value === null ? null : strtolower($value);
    }
}
