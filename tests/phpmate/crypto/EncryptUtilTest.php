<?php

namespace shali\phpmate\tests\crypto;

use PHPUnit\Framework\TestCase;
use shali\phpmate\crypto\EncryptUtil;
use shali\phpmate\PhpMateException;

class EncryptUtilTest extends TestCase
{
    /**
     * @var string 密码
     */
    private $password = '1234567890123456';

    /**
     * @throws PhpMateException
     */
    public function testEncryptByAESPKCS5Padding()
    {
        $encrypted = EncryptUtil::encryptByAES_ECB_PKCS5PaddingToBase64($this->password, 'hello world');
        $this->assertEquals('roLzT3GBhVQw22WrUPAdsw==', $encrypted);
    }

    /**
     * @throws PhpMateException
     */
    public function testDecryptByAESPKCS5Padding()
    {
        $decrypted = EncryptUtil::decryptByAES_ECB_PKCS5PaddingToBase64($this->password, 'roLzT3GBhVQw22WrUPAdsw==');
        $this->assertEquals('hello world', $decrypted);
    }
}
