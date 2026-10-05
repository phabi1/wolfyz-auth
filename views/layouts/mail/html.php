<?php
$logoUrl = $this->asset('img/logo.png');
$siteName = 'Roller Les Loups';
?>
<html>

<body
    style="margin: 0; padding: 0; background-color: #f4f7fb; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
        style="background-color: #f4f7fb; margin: 0; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                    style="max-width: 600px;">
                    <tr>
                        <td style="padding: 0 0 12px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                                style="background-color: transparent; border-radius: 0; box-shadow: none;">
                                <tr>
                                    <td align="center" style="padding: 0 24px 10px; text-align: center;">
                                        <img src="<?php echo $logoUrl; ?>" alt="<?php echo $siteName; ?>"
                                            style="display: block; max-height: 60px; width: auto; margin: 0 auto 10px; border: 0; outline: none; text-decoration: none;" />
                                        <div
                                            style="font-size: 22px; line-height: 1.3; font-weight: bold; color: #2c2c2c; letter-spacing: 0.3px; text-align: center;">
                                            <?php echo $siteName; ?>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                    style="max-width: 600px; background-color: #ffffff; border-radius: 18px; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);">
                    <tr>
                        <td
                            style="background: linear-gradient(135deg, #2c2c2c 0%, #9e1c1c 100%); padding: 28px 32px 20px; text-align: center;">
                            <div style="font-size: 30px; line-height: 1.2; font-weight: bold; color: #ffffff;">
                                <?php echo $this->layout()->block('title', ''); ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 36px 32px 24px 32px;">
                            <?php echo $this->layout()->block('content', ''); ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 32px 32px;">
                            <div
                                style="border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 12px; line-height: 1.6; color: #64748b; text-align: center;">
                                <?php echo $this->translate("Team {siteName}", ['siteName' => $siteName]); ?>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>