/**
 * Ported exactly from cats-frontend/src/lib/helpers.tsx. Since SIN is
 * one-way hashed server-side (TeamMember::setSinNumberAttribute), the
 * value passed in here is the stored hash, not the real SIN — masking its
 * tail is the only thing possible post-hash, matching the reference.
 */
export function maskSIN(value: string): string {
    const digits = value.replace(/\D/g, '');

    if (digits.length <= 3) {
        return digits;
    }

    const masked = '•'.repeat(digits.length - 3);

    return masked + digits.slice(-3);
}
