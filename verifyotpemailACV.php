<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

function send_verification($fullname, $email, $otp){

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'erwin.capuno.cics@ust.edu.ph'; // Your Gmail
        $mail->Password   = 'roht rgiq ezyn wxbs'; // Your App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('erwin.capuno.cics@ust.edu.ph', 'SERKE COVE - OTP');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "OTP Verification";
        $mail->Body    = '<h3 style="color: #2c3e50;; margin-bottom: 20px;">Hello, '.$fullname.'</h3>
<p>Thank you for signing up at <strong>Serke Cove</strong>.</p>
<p style="margin-top: 20px;">To complete your registration, please proceed to the OTP verification page
 and enter the code below to verify your email address.</p>
<p>Verification code:</p>
<div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; 
font-size: 24px; color: #004aad; font-weight: bold;">
        '.$otp.' </div>
<p style="margin-top:10px;font-size: 14px; color: #6c757d;">— Serke Admin</p>';

        $mail->send();
        echo "
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Email Successfully Sent!',
                confirmButtonText: 'OK'
            });
        </script>
        ";
    } catch (Exception $e) {
        echo "
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Email Failed!',
                text: 'Message could not be sent.',
                footer: '".$mail->ErrorInfo."'
            });
        </script>
        ";
    }
}
?>