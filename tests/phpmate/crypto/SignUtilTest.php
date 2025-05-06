<?php

namespace shali\phpmate\tests\crypto;

use PHPUnit\Framework\TestCase;
use shali\phpmate\crypto\SignUtil;
use shali\phpmate\PhpMateException;

class SignUtilTest extends TestCase
{
    private $publicKey = <<<EOF
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0nFFqD5nO97Fmoc4fblg
Km12vDp3r96zWCSjfsvpKV1wmpM93Rp5MFX+KGkpMe3XVMsPNr7SOUjrH+IDAGok
y6RBYyaZrYtyqNba+w2kWN0YejSA+NgenqhsxwiNIhgdaQPMKYMQ+X8tcRsDrUx1
LUt3QTbLnxmsIN7xlOanFKJyZwcu8i43S6CYvngjWH9f/JeI7+I8iTWVYxneSsWG
98eMVs7E9xJMi2wvTpnZ44+rmqJ9im6P0uV5yAcRdLsCs9ZRtfWuSGVH7RuIQz52
OLsrQkzTRLJ7TgfPudF6NWneH3NRh06qTb3TM/WuwE5SjJ3BToPPVd8tPC+324ET
RQIDAQAB
-----END PUBLIC KEY-----
EOF;

    private $privateKey = <<<EOF
-----BEGIN RSA PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDScUWoPmc73sWa
hzh9uWAqbXa8Onev3rNYJKN+y+kpXXCakz3dGnkwVf4oaSkx7ddUyw82vtI5SOsf
4gMAaiTLpEFjJpmti3Ko1tr7DaRY3Rh6NID42B6eqGzHCI0iGB1pA8wpgxD5fy1x
GwOtTHUtS3dBNsufGawg3vGU5qcUonJnBy7yLjdLoJi+eCNYf1/8l4jv4jyJNZVj
Gd5KxYb3x4xWzsT3EkyLbC9Omdnjj6uaon2Kbo/S5XnIBxF0uwKz1lG19a5IZUft
G4hDPnY4uytCTNNEsntOB8+50Xo1ad4fc1GHTqpNvdMz9a7ATlKMncFOg89V3y08
L7fbgRNFAgMBAAECggEAd4cu8WjAahkWU7cKNx7bqD2Ue0UaRiJP63ikBJj9Tils
k9+d+7/VpcayHXHdnCZjcB4F/ipUbYUlR26wFcQ0MhaRrSd3kkLqVUv0BTSybGbo
SEEaID1g5uzzG/mXcA4SZltp0wpG0e+Sd/PIGt6aj6eVjMz6yttiESmQPerka6rx
L3bMSNsQJjwM60VFZIvNDtPNHipOW+swP04GmoXzRERJsM5ghuKuRAu/F3VSqt9T
D0SaB8Q/rUK02C36P+rVSGJJ9AUdp3s4DeoqaxwgQDBtdUOJVM9SZFoqmXMXOjov
fkdbrMEhL1MfqIsrV6LjMBpEi+lmEqr/JO0QJv94AQKBgQDy2FTOWaX94T14OUUM
N1M2sF96CLeq1gPD1cHfHuQfYYgcpRdnMiEgGiDAc12TDu4EziAhIwOg8AAg/tkD
0qF26Vgd8Bwk8t6FGjTAPz+KzKTUjFe2fvAZv7Vsf6Kc15mZE42V6/GjFMS+Wkbr
vjoN8yu2tLWEcNpzr8gkFqEFhQKBgQDd15iaXg1ckDX9iIpmV+hQEUnbZHJkUnY7
jiA4/eiup78l0xylT9dVqPWVPLKNJIEYT6zTfQ09qX7VU854RejDnMaXF6sW62Ph
9NUeP3sEiS/wOkUCMkA2nc49rhFCnlcbjzvgxV1DMHh0qruN5W1EokQRte8uCXz7
dfu03LNiwQKBgGkkfvtk1zinx+yAp0OVxKKeFIiKs7L0vGaS60DGaDCqEruMQyi8
DJmQlnOcv3wHb8iG0mRme5C3uOaQULeV/7CzcSJtLlJVEUEByqsd904KMqeQJ/3s
0dnkJhHW5ToRIwCi9Z9eq51XRaPBBInXL92QVnHhpeG01vBVwErXvVndAoGBALtB
+baUHYM819YjI3AwVBECBu4CY+z7DoJG/jwdWAPV5SvwgAWq14GfFW3bxnwNjEsR
NjlvHXYnVMCN9YLgwBIejCON/wVhvPZGzH6z5wt1IdoN1aJ8+Gch3a2C+V7aeXzx
8wFQl+DXUVZpp9enCg0dS4gHotWhfLZmaQnKIkIBAoGBAJBUQVHOY9QGDH7xO5fa
AoaU3h1+nx0xoo3HwlrxUNDSCD2qpMELYV2T3NfyIzjEYRdf2OVLqpv05Hd0bpOl
hjaBm16ZKMBNQNyw4PL/9Q+jJ0zBAMS8jhwT6YcwW5Bi6BG6K2wqj4gMiicDYF9k
/VeAJPHRGFPjNiqBdLIioOed
-----END RSA PRIVATE KEY-----
EOF;


    /**
     * @throws PhpMateException
     */
    public function testSha256WithRsaToBase64()
    {
        $content = 'hello world';
        $sign = SignUtil::sha256WithRsaToBase64Sign($this->privateKey, $content);
        self::assertEquals('DajiMoq0vCb2ihai9ZzsMUuF2UiQcScp/naZXofCBRCh3DTQfxSTu2l1Zm6tvOzpuoEItG2+wuD2TuQ9Ym46REW+7e96F7x+rg82X1+9NHdRJK742CUFao8r+c3kCWNPN6h9SejVdzErCcrNveU+MJZPybK7kFyt8JV/fqFF29bYtSWRLSClv741NOJiiBIw9ZOB0rYHIwQ8vIuVwGkWGglpLLv2KMY58pqafVXEFWApfrYi3SXEJOKTfHVAVeGLpcc1BZ0ZY41/CIwTh1VCiedgm58/wPWXf4JvuJiN6NZe29E2r1SEnd2jFf368JZFJG0Hzmmk91pCVFWB0oNIkw==', $sign);
    }

    /**
     * @test 验证签名
     * @return void
     * @throws PhpMateException
     */
    function sha256WithRsaToBase64Verify()
    {
        $content = 'hello world';
        $sign = SignUtil::sha256WithRsaToBase64Sign($this->privateKey, $content);
        self::assertTrue(SignUtil::sha256WithRsaToBase64VerifySign($this->publicKey, $sign, $content));
    }
}
