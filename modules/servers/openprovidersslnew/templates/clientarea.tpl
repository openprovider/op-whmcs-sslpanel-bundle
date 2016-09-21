<h2 align="left">Additional product data</h2>

{if $errorMessage} {$errorMessage} {/if}

<table class="frame" width="100%" cellspacing="0" cellpadding="0">
    <tbody>
    <tr>
        <td>
            <table width="100%" cellspacing="0" cellpadding="10" border="0">
                <tbody>
                <tr>
                    <td class="fieldarea" width="150">{$linkName}:</td>
                    <td><a href="./generateSslPanelOtpToken.php?{$linkValue}" target="_blank">open</a></td>
                </tr>
                <tr>
                    <td class="fieldarea" width="150">status</td>
                    <td>{$status}</td>
                </tr>
                <tr>
                    <td class="fieldarea" width="150">creation date</td>
                    <td>{$creationDate}</td>
                </tr>
                <tr>
                    <td class="fieldarea" width="150">activation date</td>
                    <td>{$activationDate}</td>
                </tr>
                <tr>
                    <td class="fieldarea" width="150">expiration date</td>
                    <td>{$expirationDate}</td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>
