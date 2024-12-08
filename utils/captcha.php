<?php
session_start();

class Captcha {
    private $width = 100;
    private $height = 30;
    private $codeNum = 4;
    private $code;
    private $image;

    public function __construct() {
        $this->code = $this->createCode();
        $_SESSION['captcha'] = strtolower($this->code);
    }

    // 生成随机验证码
    private function createCode() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < $this->codeNum; $i++) {
            $code .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $code;
    }

    // 生成验证码图片
    public function createImage() {
        $this->image = imagecreatetruecolor($this->width, $this->height);
        $bgcolor = imagecolorallocate($this->image, 255, 255, 255);
        imagefill($this->image, 0, 0, $bgcolor);
        
        // 添加干扰线
        for ($i = 0; $i < 6; $i++) {
            $color = imagecolorallocate($this->image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imageline($this->image, mt_rand(0, $this->width), mt_rand(0, $this->height), 
                     mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }

        // 添加干扰点
        for ($i = 0; $i < 50; $i++) {
            $color = imagecolorallocate($this->image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($this->image, mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }

        // 将验证码写入图片
        for ($i = 0; $i < $this->codeNum; $i++) {
            $color = imagecolorallocate($this->image, mt_rand(0, 150), mt_rand(0, 150), mt_rand(0, 150));
            imagestring($this->image, 5, $i * 25 + 5, mt_rand(5, 10), $this->code[$i], $color);
        }

        header('Content-Type: image/png');
        imagepng($this->image);
        imagedestroy($this->image);
    }
}

$captcha = new Captcha();
$captcha->createImage();
?> 