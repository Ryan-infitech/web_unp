<?php
// mailer.php - wrapper that uses PHPMailer if available, otherwise falls back to mail()
$cfg = require __DIR__ . '/mailer_config.php';

function send_mail($to, $subject, $body, $is_html = false){
  global $cfg;

  // If configured to use SMTP and PHPMailer is available
  if($cfg['use_smtp']){
    if(file_exists(__DIR__ . '/../vendor/autoload.php')){
      require_once __DIR__ . '/../vendor/autoload.php';
      $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
      try {
        $mail->isSMTP();
        $mail->Host = $cfg['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $cfg['smtp_user'];
        $mail->Password = $cfg['smtp_pass'];
        $mail->SMTPSecure = $cfg['smtp_secure'];
        $mail->Port = $cfg['smtp_port'];
        $mail->SMTPOptions = array(
          'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
          )
        );

        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML($is_html);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $result = $mail->send();
        error_log("Email sent to $to - Status: " . ($result ? 'Success' : 'Failed'));
        return $result;
      } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        // fallback to mail()
      }
    }
  }

  // fallback to PHP mail()
  $headers = "From: " . ($cfg['from_email'] ?? 'no-reply@localhost') . "\r\n";
  if($is_html) {
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
  } else {
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
  }
  $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
  
  $result = @mail($to, $subject, $body, $headers);
  error_log("Mail function to $to - Status: " . ($result ? 'Success' : 'Failed'));
  return $result;
}

function send_html_mail($to, $subject, $html_body){
  return send_mail($to, $subject, $html_body, true);
}
