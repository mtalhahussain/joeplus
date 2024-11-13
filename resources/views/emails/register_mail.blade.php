
<table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style=" margin-top: 5rem;">
    <tr>
      <td align="center">
        <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background: #fff;">

          <tr>
            <td class="email-body" width="570" cellpadding="0" cellspacing="0">
              <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                
                <tr>
                  <td class="content-cell" style="box-sizing: border-box;font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';position: relative;max-width: 100vw;padding: 40px;border: 1px solid #eee;border-radius: 12px;">
                  <p style="color: #666666; font-size: 16px;">We have received a request to verify your identity. Please use the OTP code below to proceed with your action:</p>
                  
                    <div class="f-fallback">
                      <p style="color: #666666; font-size: 16px;">Your OTP code is: <b style="font-size: 16px; color:#333">{{ $otp }}</b></p>
                    </div>
                    <p style="color: #666666; font-size: 14px;">For security reasons, this OTP is valid for the next {{ $expire }} minutes. Please do not share this code with anyone. If you did not request this, please contact our support team immediately.</p>
                    <br/>
                    <p style="color: #666666; font-size: 14px;">&copy; {{ \Carbon\Carbon::now()->format('Y') }} All Rights Reserved.</p>
                  </td>
                </tr>
  
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>

  </table>

