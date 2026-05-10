<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-08-05
 * Time: 16:03
 */

namespace Oasis\Mlib\Utils;

class CaesarCipher
{
    private array $lookupTable  = [];
    private array $reverseTable = [];
    
    public function __construct(
        private readonly int $bits = 32,
        private readonly int $partition = 8,
        private readonly int $strength = 5,
    ) {
        if ($partition % 2 != 0) {
            throw new \InvalidArgumentException("partition should be an even number!");
        }
        if ($bits > 64 || $bits <= 0 || ($bits % 2 != 0)) {
            throw new \InvalidArgumentException("bits should be an even number in range (0, 64]");
        }
        if ($bits % $partition != 0) {
            throw new \InvalidArgumentException("the bits must be divisable by partition!");
        }
        if ($strength < 5) {
            //throw new \InvalidArgumentException("minimum strength should be 5");
        }
    }
    
    public function decrypt(int|string $number): int|string
    {
        if (is_string($number)) {
            return $this->decryptString($number);
        }
        
        $mask = 0;
        for ($i = 0; $i < $this->bits; ++$i) {
            $mask = ($mask << 1) + 1;
        }
        $number = $number & $mask;
        
        $step     = $this->partition / 2;
        $stepMask = 0;
        for ($i = 0; $i < $step; ++$i) {
            $stepMask = ($stepMask << 1) + 1;
        }
        
        $partitionMask = 0;
        for ($i = 0; $i < $this->partition; ++$i) {
            $partitionMask = ($partitionMask << 1) + 1;
        }
        
        for ($i = 0; $i < $this->strength; ++$i) {
            // reverse translate
            $result = 0;
            for ($j = 0; $j < $this->bits / $this->partition; ++$j) {
                $current = $number & $partitionMask;
                $current = $this->reverseLookup($current);
                $number  = CommonUtils::unsignedRightShift($number, $this->partition);
                $result  = ($result << $this->partition) | $current;
            }
            $number = $result;
            
            // rotate
            $high   = $number & $stepMask;
            $high   = $high << ($this->bits - $step);
            $low    = CommonUtils::unsignedRightShift($number, $step);
            $number = $high | $low;
        }
        
        return $number;
    }
    
    protected function decryptString(string $str): string
    {
        if ($this->bits % 8 != 0) {
            throw new \RuntimeException("Number of bits should be divisable by 8 when encrypting a string!");
        }
        $step = $this->bits / 8;
        
        $size = strlen($str);
        if (!$size) {
            return '';
        }
        
        $compensation = unpack("C", $str)[1]; // NOTE: unpacked string has index from 1, not 0
        if ($compensation < 0 || $compensation > PHP_INT_SIZE) {
            throw new \RuntimeException("Malformed string header");
        }
        
        $result = '';
        for ($offset = 1; $offset < $size; $offset += $step) {
            $unpacked = unpack('C*', substr($str, $offset, $step));
            
            $val   = 0;
            $shift = 0;
            foreach ($unpacked as $num) {
                $num = $num << $shift;
                $shift += 8;
                $val += $num;
            }
            $decrypted = $this->decrypt($val);
            $packed    = [];
            for ($i = 0; $i < $step; ++$i) {
                $partition = $decrypted & 0xff;
                $packed[]  = pack('C', $partition);
                $decrypted = CommonUtils::unsignedRightShift($decrypted, 8);
            }
            $packed = array_reverse($packed);
            foreach ($packed as $char) {
                $result .= $char;
            }
        }
        
        return substr($result, 0, $size - 1 - $compensation);
    }
    
    public function encrypt(int|string $number): int|string
    {
        if (is_string($number)) {
            return $this->encryptString($number);
        }
        
        $mask = 0;
        for ($i = 0; $i < $this->bits; ++$i) {
            $mask = ($mask << 1) + 1;
        }
        $number = $number & $mask;
        
        $step     = $this->partition / 2;
        $stepMask = 0;
        for ($i = 0; $i < $step; ++$i) {
            $stepMask = ($stepMask << 1) + 1;
        }
        
        $partitionMask = 0;
        for ($i = 0; $i < $this->partition; ++$i) {
            $partitionMask = ($partitionMask << 1) + 1;
        }
        
        for ($i = 0; $i < $this->strength; ++$i) {
            // rotate
            $low    = CommonUtils::unsignedRightShift($number, ($this->bits - $step)) & $stepMask;
            $high   = $number << $step;
            $high   = $high & $mask;
            $number = $high | $low;
            
            // translate
            $result = 0;
            for ($j = 0; $j < $this->bits / $this->partition; ++$j) {
                $current = $number & $partitionMask;
                $current = $this->lookup($current);
                $number  = CommonUtils::unsignedRightShift($number, $this->partition);
                $result  = ($result << $this->partition) | $current;
            }
            $number = $result;
        }
        
        return $number;
    }
    
    protected function encryptString(string $str): string
    {
        if ($this->bits % 8 != 0) {
            throw new \RuntimeException("Number of bits should be divisable by 8 when encrypting a string!");
        }
        $step = $this->bits / 8;
        
        $size = strlen($str);
        if (!$size) {
            return '';
        }
        
        $result       = '';
        $compensation = 0;
        for ($offset = 0; $offset < $size; $offset += $step) {
            $substr = substr($str, $offset, $step);
            if (strlen($substr) != $step) {
                $compensation = $step - strlen($substr);
                $substr .= str_repeat("\x00", $compensation);
            }
            $unpacked = unpack('C*', $substr);
            $val      = 0;
            $first    = true;
            foreach ($unpacked as $num) {
                if ($first) {
                    $first = false;
                }
                else {
                    $val <<= 8;
                }
                $val += $num & 0xff;
            }
            $encrypted = $this->encrypt($val);
            for ($i = 0; $i < $step; ++$i) {
                $partition = $encrypted & 0xff;
                $packed    = pack('C', $partition);
                $result .= $packed;
                $encrypted = CommonUtils::unsignedRightShift($encrypted, 8);
            }
        }
        
        return pack('C', $compensation) . $result;
    }
    
    protected function lookup(int $k): int
    {
        if (!$this->lookupTable) {
            $this->prepareLookupTable();
        }
        
        return $this->lookupTable[$k];
    }
    
    protected function prepareLookupTable(): void
    {
        $lookupTable = [];
        for ($i = 0; $i < pow(2, $this->partition); ++$i) {
            $lookupTable[$i] = $i;
        }
        shuffle($lookupTable);
        $this->setLookupTable($lookupTable);
    }
    
    protected function reverseLookup(int $k): int
    {
        if (!$this->reverseTable) {
            $this->prepareLookupTable();
        }
        
        return $this->reverseTable[$k];
    }
    
    public function getBits(): int
    {
        return $this->bits;
    }
    
    public function getLookupTable(): array
    {
        if (!$this->lookupTable) {
            $this->prepareLookupTable();
        }
        
        return $this->lookupTable;
    }
    
    public function setLookupTable(array $lookupTable): void
    {
        $this->lookupTable  = $lookupTable;
        $this->reverseTable = [];
        foreach ($this->lookupTable as $k => $v) {
            $this->reverseTable[$v] = $k;
        }
    }
    
    public function getPartition(): int
    {
        return $this->partition;
    }
    
    public function getStrength(): int
    {
        return $this->strength;
    }
    
}
