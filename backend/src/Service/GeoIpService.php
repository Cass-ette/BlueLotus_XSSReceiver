<?php

declare(strict_types=1);

namespace App\Service;

/**
 * IP 归属地查询，基于 qqwry.dat（Discuz X3.1 算法）
 */
class GeoIpService
{
    private string $dataFile;

    public function __construct(string $dataFile)
    {
        $this->dataFile = $dataFile;
    }

    public function lookup(string $ip): string
    {
        if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $ip)) {
            return '未知';
        }

        $parts = explode('.', $ip);

        // LAN detection
        if ($parts[0] == 10 || $parts[0] == 127
            || ($parts[0] == 192 && $parts[1] == 168)
            || ($parts[0] == 172 && $parts[1] >= 16 && $parts[1] <= 31)) {
            return '局域网';
        }

        if ($parts[0] > 255 || $parts[1] > 255 || $parts[2] > 255 || $parts[3] > 255) {
            return '错误ip';
        }

        if (!file_exists($this->dataFile)) {
            return '未知';
        }

        $fd = @fopen($this->dataFile, 'rb');
        if (!$fd) {
            return 'ip库出错';
        }

        $ipNum = $parts[0] * 16777216 + $parts[1] * 65536 + $parts[2] * 256 + $parts[3];

        $DataBegin = fread($fd, 4);
        $DataEnd = fread($fd, 4);
        if (!$DataBegin || !$DataEnd) {
            fclose($fd);
            return '未知';
        }

        $ipbegin = implode('', unpack('L', $DataBegin));
        if ($ipbegin < 0) $ipbegin += pow(2, 32);
        $ipend = implode('', unpack('L', $DataEnd));
        if ($ipend < 0) $ipend += pow(2, 32);
        $ipAllNum = ($ipend - $ipbegin) / 7 + 1;

        $BeginNum = $ip2num = $ip1num = 0;
        $ipAddr1 = $ipAddr2 = '';
        $EndNum = $ipAllNum;

        while ($ip1num > $ipNum || $ip2num < $ipNum) {
            $Middle = intval(($EndNum + $BeginNum) / 2);

            fseek($fd, $ipbegin + 7 * $Middle);
            $ipData1 = fread($fd, 4);
            if (strlen($ipData1) < 4) {
                fclose($fd);
                return '系统错误';
            }
            $ip1num = implode('', unpack('L', $ipData1));
            if ($ip1num < 0) $ip1num += pow(2, 32);

            if ($ip1num > $ipNum) {
                $EndNum = $Middle;
                continue;
            }

            $DataSeek = fread($fd, 3);
            if (strlen($DataSeek) < 3) {
                fclose($fd);
                return '系统错误';
            }
            $DataSeek = implode('', unpack('L', $DataSeek . chr(0)));
            fseek($fd, $DataSeek);
            $ipData2 = fread($fd, 4);
            if (strlen($ipData2) < 4) {
                fclose($fd);
                return '系统错误';
            }
            $ip2num = implode('', unpack('L', $ipData2));
            if ($ip2num < 0) $ip2num += pow(2, 32);

            if ($ip2num < $ipNum) {
                if ($Middle == $BeginNum) {
                    fclose($fd);
                    return '未知';
                }
                $BeginNum = $Middle;
            }
        }

        $ipFlag = fread($fd, 1);
        if ($ipFlag == chr(1)) {
            $ipSeek = fread($fd, 3);
            if (strlen($ipSeek) < 3) {
                fclose($fd);
                return '系统错误';
            }
            $ipSeek = implode('', unpack('L', $ipSeek . chr(0)));
            fseek($fd, $ipSeek);
            $ipFlag = fread($fd, 1);
        }

        if ($ipFlag == chr(2)) {
            $AddrSeek = fread($fd, 3);
            if (strlen($AddrSeek) < 3) {
                fclose($fd);
                return '系统错误';
            }
            $ipFlag = fread($fd, 1);
            if ($ipFlag == chr(2)) {
                $AddrSeek2 = fread($fd, 3);
                if (strlen($AddrSeek2) < 3) {
                    fclose($fd);
                    return '系统错误';
                }
                $AddrSeek2 = implode('', unpack('L', $AddrSeek2 . chr(0)));
                fseek($fd, $AddrSeek2);
            } else {
                fseek($fd, -1, SEEK_CUR);
            }

            while (($char = fread($fd, 1)) != chr(0)) {
                $ipAddr2 .= $char;
            }

            $AddrSeek = implode('', unpack('L', $AddrSeek . chr(0)));
            fseek($fd, $AddrSeek);

            while (($char = fread($fd, 1)) != chr(0)) {
                $ipAddr1 .= $char;
            }
        } else {
            fseek($fd, -1, SEEK_CUR);
            while (($char = fread($fd, 1)) != chr(0)) {
                $ipAddr1 .= $char;
            }

            $ipFlag = fread($fd, 1);
            if ($ipFlag == chr(2)) {
                $AddrSeek2 = fread($fd, 3);
                if (strlen($AddrSeek2) < 3) {
                    fclose($fd);
                    return '系统错误';
                }
                $AddrSeek2 = implode('', unpack('L', $AddrSeek2 . chr(0)));
                fseek($fd, $AddrSeek2);
            } else {
                fseek($fd, -1, SEEK_CUR);
            }
            while (($char = fread($fd, 1)) != chr(0)) {
                $ipAddr2 .= $char;
            }
        }
        fclose($fd);

        if (function_exists('iconv')) {
            $ipAddr1 = @iconv('gb18030', 'utf-8//IGNORE', $ipAddr1) ?: $ipAddr1;
            if ($ipAddr2) {
                if (ord($ipAddr2[0]) == 2) {
                    $ipAddr2 = '';
                } else {
                    $ipAddr2 = @iconv('gb18030', 'utf-8//IGNORE', $ipAddr2) ?: $ipAddr2;
                }
            }
        }

        if (preg_match('/http/i', $ipAddr2)) {
            $ipAddr2 = '';
        }

        $ipaddr = trim($ipAddr1 . $ipAddr2);
        $ipaddr = preg_replace('/CZ88\.NET/is', '', $ipaddr);
        $ipaddr = trim($ipaddr);

        if (preg_match('/http/i', $ipaddr) || $ipaddr === '') {
            return '未知';
        }

        return htmlspecialchars($ipaddr, ENT_QUOTES, 'UTF-8');
    }
}
