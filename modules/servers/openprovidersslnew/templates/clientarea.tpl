<h2 align="left">SSL Certificate Status</h2>

{if $errorMessage} {$errorMessage} {/if}

<table class="frame" width="100%" cellspacing="0" cellpadding="0">
    <tbody>
        <tr>
            <td>
                <table width="100%" cellspacing="0" cellpadding="10" border="0">
                    <tbody>
                        <tr>
                            <td class="fieldarea" width="150">{$linkName}:</td>
                            <td>
                                <a href="{$systemurl|regex_replace:"/\/$/":""}/generateSslPanelOtpToken.php?{$linkValue}"
                                   target="_blank">Open</a></td>
                        </tr>
                        <tr>
                            <td class="fieldarea" width="150">Status</td>
                            <td>{$status}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea" width="150">Created at</td>
                            <td>{$creationDate}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea" width="150">Activated at</td>
                            <td>{$activationDate}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea" width="150">Expire at</td>
                            <td>{$expirationDate}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>
