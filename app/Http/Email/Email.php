<?php

namespace App\Http\Email;

use App\Models\Employee;
use App\Models\Settings;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Email
{
public function send(string $title, string $body, string $address,string $name): void{
    $settings = Settings::where('id',1)->first();
    if($settings->email_enabled == 1){
        $mail = new PHPMailer(false);
        try {
            //Server settings
            $mail->SMTPDebug = 1;
            $mail->isSMTP();
            $mail->Host       = 'connect.smtp.bz';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'axobeast@gmail.com';
            $mail->Password   = 'nilv02MFViZv';
            $mail->SMTPSecure = 'tls';
            $mail->CharSet = 'UTF-8';
            $mail->Port       = 25;

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            //Recipients
            $mail->setFrom('info@axobeast.ru', $name);
            $mail->addAddress($address, '');

            //Content
            $mail->isHTML(true);
            $mail->Subject = $title;
            $mail->Body    = '<html>'.$body.'</html>';

            $mail->send();


        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

}
}
