<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function kirimNotifikasiEmail($to_email, $to_name, $id_laporan, $lokasi, $status_baru) {
    $mail = new PHPMailer(true);
    try {
        // Konfigurasi SMTP Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'emailmu@gmail.com';     // Ganti dengan email Gmail kamu
        $mail->Password   = 'app_password_gmail';    // App Password (bukan password biasa)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('emailmu@gmail.com', 'PantauJalan Surabaya');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = "Update Status Laporan #$id_laporan - PantauJalan Surabaya";
        
        $badge_color = match($status_baru) {
            'Dikirim'  => '#6c757d',
            'Diproses' => '#ffc107',
            'Selesai'  => '#28a745',
            default    => '#6c757d'
        };

        $mail->Body = "
        <div style='font-family:Poppins,Arial,sans-serif;max-width:600px;margin:0 auto;background:#f8f9fa;padding:20px;'>
          <div style='background:#1a1a2e;padding:20px;border-radius:12px 12px 0 0;text-align:center;'>
            <h2 style='color:#c8a96e;margin:0;'>🛣️ PantauJalan Surabaya</h2>
          </div>
          <div style='background:#ffffff;padding:30px;border-radius:0 0 12px 12px;'>
            <p style='color:#333;font-size:16px;'>Halo, <strong>$to_name</strong></p>
            <p style='color:#555;'>Status laporan kamu telah diperbarui:</p>
            <table style='width:100%;border-collapse:collapse;margin:20px 0;'>
              <tr><td style='padding:10px;background:#f1f3f5;color:#555;width:40%;border-radius:6px;'>ID Laporan</td>
                  <td style='padding:10px;color:#333;font-weight:600;'>#$id_laporan</td></tr>
              <tr><td style='padding:10px;color:#555;'>Lokasi</td>
                  <td style='padding:10px;color:#333;'>$lokasi</td></tr>
              <tr><td style='padding:10px;background:#f1f3f5;color:#555;border-radius:6px;'>Status Terbaru</td>
                  <td style='padding:10px;'>
                    <span style='background:$badge_color;color:#fff;padding:4px 12px;border-radius:20px;font-size:14px;font-weight:600;'>$status_baru</span>
                  </td></tr>
            </table>
            <p style='color:#777;font-size:13px;margin-top:30px;'>Terima kasih telah melaporkan. Bersama kita jaga jalan Surabaya!</p>
          </div>
        </div>";

        $mail->AltBody = "Halo $to_name, status laporan #$id_laporan di $lokasi telah diubah menjadi: $status_baru";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email gagal: " . $mail->ErrorInfo);
        return false;
    }
}