<?php

namespace shali\phpmate\tests\crypto;

use PHPUnit\Framework\TestCase;
use shali\phpmate\core\util\StrUtil;
use shali\phpmate\crypto\KeyUtil;
use shali\phpmate\crypto\SignUtil;
use shali\phpmate\PhpMateException;

class SignUtilTest extends TestCase
{
    /**
     * 公钥加密，验签
     */
    private const PUBLIC_KEY = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAukkM725cFg/vyEIRsJvwCT+CpM+ZYkowDqS+za6vOAEHgQlwdgdiE8a3FL97ZwwRtr8BPXwgODBbH0ujOraPexsE1DpGwYovrQ0YVlUtFHEuib3QALjDiHWQ5l1o9Zi1Te9QElrtUBDMbsQKHsZHWgsSrhDrJq3bhRm8+c8F/hnUxddESX/cUWZQzPW/0QC9xhSe7oACFjlGOB71dtwoFbGisTaJGMurAPivlGNYs8Ou2r2Jyn0iZHQBN9hpsV8cRmODyohdHjf9qCY+hbmKTwBnQ0felQ5DtWXweKLPRVQBC6EOxopSinmFnGKvEyWhQO7LBGlfJ34OeCagMEkb2wIDAQAB';

    /**
     * 私钥解密，签名
     */
    private const PRIVATE_KEY = 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC6SQzvblwWD+/IQhGwm/AJP4Kkz5liSjAOpL7Nrq84AQeBCXB2B2ITxrcUv3tnDBG2vwE9fCA4MFsfS6M6to97GwTUOkbBii+tDRhWVS0UcS6JvdAAuMOIdZDmXWj1mLVN71ASWu1QEMxuxAoexkdaCxKuEOsmrduFGbz5zwX+GdTF10RJf9xRZlDM9b/RAL3GFJ7ugAIWOUY4HvV23CgVsaKxNokYy6sA+K+UY1izw67avYnKfSJkdAE32GmxXxxGY4PKiF0eN/2oJj6FuYpPAGdDR96VDkO1ZfB4os9FVAELoQ7GilKKeYWcYq8TJaFA7ssEaV8nfg54JqAwSRvbAgMBAAECggEARvRdL3OvMp4WXIZB263Bw5wDxIfoagZNAL7iiFCBoAjQVWeFhQdx5Yt6n7YBqHHx61QcglFdqllM1AJI5au0whS8BaQ+4Cgk2brTqsqdtZwYuFFqwWOe4sK5Eu3AdU+Zu1osexlUK/uCCqy0GB24/sSZ9GAwWVa+dxejIdmndC22YF0gzlLsfQvP6PSaJ474kel1DQKrg1vaLXirCKyhGwyaWMuaFV1syy1tz7XnJcQ+/8x3CGif2NjwpMPvTzpVEd/OyWcvLx2YPUwPQwf9OY9Hm19AQmy+B9MSffBGw8uSJtwIMpU5KqZAljPd8sCqnJR3DNHXqbHTGZ/cOiKJ4QKBgQDx8dgfJmTCpsIX1YUtPar1P6OGXBhxrKtj/54Oe371rWcqFvRkyBNL2CpNYP+zLEXoysoqAWfuDczh4qasIU+gqJXQXcI9jwYeIpmIG8zGE0L4yanLANRf3oGbhfrG/DCWdUlMIFrVRzeHRiuWhB06+iR3N5EC7OAr3auYoXEfOwKBgQDFG3N0areDHNuU32AXTd50mfe+kivp1saDdnknfrfsZF23Zbcm4nbo9G3+TTcZQnAR3ACdl0Fka8/WG3Sn9UZHiudzU5uFgpp9YkdAksedIQuPev+QE9q3IrGLPrExhuh/7e/xzA832wh9ONLiR+fJ7kE8t68swaT1Vy4XDH1r4QKBgQCepucAri1+ktlNxb8Zxol3Xq69aWDCEecloLYlawf61CWFLR4/hA9bObmrmgKynEKPf4MH/noHWVdTfEutLf7ILCRpSUIZGdN6KVgiL5CdBn5xI9RKgRXCc+brc/TZTQATeX+CAultV9DqzLHCdomwZd1Jq89Uar4pJafjY2IJhwKBgBzzbT/aNN4jLPVu4dRKcbQ6sTLikWSlUT8Z9a2hZS5ph4JahE5H0SNiU42YldE1+vQElmqPPuGbHEnceoP4+LulYV2FGEDB8CMefkyzwnIH2oTWkhb9c5CWnfFP4gLeR+QSdL3VNR8FIvgRkpf968OzJQ3gBPDT+IC4r2JfSUTBAoGBAO6a2/aZViAgNAr6YrZAz+F6psA5HGDMXnq5a2GNSm41E4zUA+rBVRM2/BxMN2lC37OhOUUnLYZe47gcXVQVujxloO3WEzaxl2PUKGczdPpx5TERpi+ikn13mMgKX1zZQ9NPx+IdXlVzt7TOsCetYJ/jMjAF7+JNGpcXxchX5spg';

    /**
     * @throws PhpMateException
     */
    public function testSha256WithRsaToBase64()
    {
        $params = [
            'name' => 'shali',
            'age' => 23,
        ];
        $content = StrUtil::httpBuildQuery($params, true);
        $privateKey = KeyUtil::toPrivateKeyValueOfBase64Str(self::PRIVATE_KEY);
        $sign = SignUtil::signBySHA256withRSAToBase64($privateKey, $content);
        // 验证力 pos 签名
        self::assertEquals('McGBSimX/RDBMASq4s1G8SLNnjiVe6D8juQyKfSQLXsbOdeR5W4oawcvqwVMlyW8TNKTiHaI7CrbKC2AUExjBemxM3YGZtvJbGQEGdvLOjdyaX27WknWC66tKIKaRLveSS6uzU68tNpQGffk/CW/YBXgFtb132x2s9UwXYGLdA9MQNy6LinzgFM6mi1rE73tPP74CuEEX4+2GuyRxEj4MQ6cXYtbCsgiFKHmtj0O5ADV+uRDhTHmworXt6Am7TA5GIcDLhb69thv6HbbFNrbPb+Qa3cJkUG9/IfTe9hXYoAxrByHx7DbOMIEJPNFOw1hwLXJbV7569ZLpfkMk0rnYA==', $sign);
    }

    /**
     * @test 验证签名
     * @return void
     * @throws PhpMateException
     */
    function sha256WithRsaToBase64Verify()
    {
        $content = 'age=23&name=shali';
        $privateKey = KeyUtil::toPrivateKeyValueOfBase64Str(self::PRIVATE_KEY);
        $sign = SignUtil::signBySHA256withRSAToBase64($privateKey, $content);
        $publicKey = KeyUtil::toPublicKeyValueOfBase64Str(self::PUBLIC_KEY);
        self::assertTrue(SignUtil::verifySignBySHA256withRSAToBase64($publicKey, $sign, $content));
    }
}
