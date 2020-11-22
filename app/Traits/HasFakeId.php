<?php

namespace App\Traits;


trait HasFakeId {

    static $FAKEID_KEY = 'PoemWikikiWmeoP'; // Symmetric-key for xor
    static $FAKEID_SPARSE = 96969696969;
    /**
     * @return string A xor encrypted string
     */
    public static function getFakeId($id) {
        return base64_encode(gmp_xor(gmp_mul($id, gmp_init(self::$FAKEID_SPARSE)), mb_ord(self::$FAKEID_KEY)));
    }

    /**
     * @param $fakeId
     * @return false|string The decrypted id of poem
     */
    public static function getIdFromFakeId($fakeId) {
        $decoded = base64_decode($fakeId);
        if (!is_numeric($decoded)) {
            return false;
        }
        $v = gmp_divexact(gmp_xor($decoded, mb_ord(self::$FAKEID_KEY)), gmp_init(self::$FAKEID_SPARSE));
        return gmp_strval($v);
    }

    /**
     * @return string A xor encrypted string
     */
    public function getFakeIdAttribute() {
        return self::getFakeId($this->id);
    }
}
