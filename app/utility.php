<?php

class UUID{

    private const GREGORIAN_TO_UNIX_INTERVALS = 0x01b21dd213814000;

    public static function generateOrderedBytes() : string {
        $timeArray = gettimeofday();
        $seconds = $timeArray["sec"];
        $microseconds = $timeArray["usec"];
        // This is the number of 100 nano-second intervals since the gregorian calendar epoch.
        $uuidTime = ((int) $seconds * 10000000)
                  + ((int) $microseconds * 10)
                  + self::GREGORIAN_TO_UNIX_INTERVALS;

        // Padded to ensure we fit the spec.
        $uuidTimeHex = str_pad(dechex($uuidTime), 16, '0', STR_PAD_LEFT);
        $uuidBytes = hex2bin($uuidTimeHex);
        // We need to be able to do bitwise operations. So we have to convert to 16 bit unsigned ints.
        $uuidBytes = unpack('n*', $uuidBytes);

        $timeHigh = $uuidBytes[1];
        $timeHigh = $timeHigh & 0x0fff; // Apply mask
        $timeHigh |= 0x1000; // version number
        $uuidBytes[1] = $timeHigh;
        
        $uuidBytes = pack('n*', $uuidBytes[1], $uuidBytes[2], $uuidBytes[3], $uuidBytes[4]);

        // NOTE(Trystan): This isn't quite to spec. The spec would store a clock sequence,
        // then update on either restart, network card update, or if we generate more than X uuid's per second.
        $clockSequence = random_int(0, 0x3fff);
        $clockSequence = $clockSequence & 0x3fff;
        $clockSequence |= 0x8000; // apply the variant.
        $clockSequenceBytes = pack('n*', $clockSequence);

        $uuidBytes .= $clockSequenceBytes . hex2bin(MAC_ADDRESS);

        return $uuidBytes;
    }

    public static function orderedBytesToArrangedString(string $bytes = NULL) : string {
        if(empty($bytes)) return "";
        // NOTE(Trystan): There might be some optimizations to do on this string arrangements and insertions.
        $arrangedBytes = $bytes[4]  . $bytes[5]  . $bytes[6] . $bytes[7]
                       . $bytes[2]  . $bytes[3]
                       . $bytes[0]  . $bytes[1]
                       . $bytes[8]  . $bytes[9]  . $bytes[10] . $bytes[11] // The rest are appended normally.
                       . $bytes[12] . $bytes[13] . $bytes[14] . $bytes[15];

        $hexString = bin2hex($arrangedBytes);

        $hexString = substr($hexString, 0, 8)  . '-' . substr($hexString, 8);
        $hexString = substr($hexString, 0, 13) . '-' . substr($hexString, 13);
        $hexString = substr($hexString, 0, 18) . '-' . substr($hexString, 18);
        $hexString = substr($hexString, 0, 23) . '-' . substr($hexString, 23);
        
        return $hexString;
    }

    public static function generateArrangedString() : string {
        $bytes = self::generateOrderedBytes();
        return self::orderedBytesToArrangedString($bytes);
    }

    public static function arrangedStringToOrderedBytes(string $uuid) : string {
        // get rid of hyphens.
        $result = str_replace('-', '', $uuid);
        $result = hex2bin($result);
        // order the bytes.
        $result = $result[6] . $result[7] . $result[4] . $result[5]
                . $result[0] . $result[1]
                . $result[2] . $result[3]
                . $result[8]  . $result[9]  . $result[10] . $result[11] // The rest are appended normally.
                . $result[12] . $result[13] . $result[14] . $result[15];
        return $result;
    }
}

?>
