<?php
namespace local_subscriptions\security;

defined('MOODLE_INTERNAL') || die();

final class TempPassword {
    /**
     * Génère un mot de passe temporaire lisible (sans 0/O, 1/l/I, etc.).
     * @param int $len   longueur SANS tirets (ex: 12)
     * @param int $group taille d’un groupe (ex: 4) ; 0 = pas de groupage
     */
    public static function generate(int $len = 12, int $group = 4): string {
        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz'; // pas I O i l o
        $digits  = '234679';                                         // pas 0 1 5 8
        $alphabet = $letters . $digits;

        $bytes = random_bytes($len * 2);
        $out = '';
        for ($i = 0, $n = strlen($bytes); strlen($out) < $len && $i < $n; $i++) {
            $out .= $alphabet[ord($bytes[$i]) % strlen($alphabet)];
        }
        // Garantir au moins une lettre et un chiffre.
        if (!preg_match('/[A-Za-z]/', $out)) {
            $out[0] = $letters[random_int(0, strlen($letters)-1)];
        }
        if (!preg_match('/\d/', $out)) {
            $out[1] = $digits[random_int(0, strlen($digits)-1)];
        }
        return ($group > 0) ? trim(chunk_split($out, $group, '-'), '-') : $out;
    }
}
