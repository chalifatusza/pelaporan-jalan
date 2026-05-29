<div style="font-family: 'Poppins', Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa; padding: 20px;">
  <div style="background: #1a1a2e; padding: 20px; border-radius: 12px 12px 0 0; text-align: center;">
    <h2 style="color: #c8a96e; margin: 0;">🛣️ PantauJalan Surabaya</h2>
  </div>
  <div style="background: #ffffff; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
    <p style="color: #333; font-size: 16px;">Halo, <strong>{{ $toName }}</strong></p>
    <p style="color: #555;">Status laporan Anda telah diperbarui oleh administrator:</p>
    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
      <tr>
        <td style="padding: 10px; background: #f1f3f5; color: #555; width: 40%; border-radius: 6px; font-weight: 500;">ID Laporan</td>
        <td style="padding: 10px; color: #333; font-weight: 600;">#{{ $idLaporan }}</td>
      </tr>
      <tr>
        <td style="padding: 10px; color: #555; font-weight: 500;">Lokasi</td>
        <td style="padding: 10px; color: #333;">{{ $lokasi }}</td>
      </tr>
      <tr>
        <td style="padding: 10px; background: #f1f3f5; color: #555; border-radius: 6px; font-weight: 500;">Status Terbaru</td>
        <td style="padding: 10px;">
          @php
            $badgeColor = match(strtolower($statusBaru)) {
                'dikirim' => '#6c757d',
                'diproses' => '#17a2b8',
                'selesai' => '#28a745',
                default => '#6c757d'
            };
          @endphp
          <span style="background: {{ $badgeColor }}; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 14px; font-weight: 600; text-transform: capitalize;">
            {{ $statusBaru }}
          </span>
        </td>
      </tr>
    </table>
    <p style="color: #777; font-size: 13px; margin-top: 30px; line-height: 1.5;">
      Terima kasih telah melaporkan kerusakan jalan. Partisipasi aktif Anda membantu menjadikan jalan Kota Surabaya lebih aman dan nyaman untuk semua orang.
    </p>
  </div>
</div>
