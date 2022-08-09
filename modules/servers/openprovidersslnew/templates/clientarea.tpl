<h2 align="left">{$LANG.opsslnew_title}</h2>

{if $errorMessage} {$errorMessage} {/if}

<table class="frame" width="100%" cellspacing="0" cellpadding="0">
    <tbody>
        <tr>
            <td>
                <table width="100%" cellspacing="0" cellpadding="10" border="0" id="op-info-tbl">
                    <tbody>
                        <tr>
                            <td class="fieldarea" width="150">{$LANG.opsslnew_panel}</td>
                            <td>
                                <a href="{$linkUrl}"
                                   target="_blank">Open</a></td>
                        </tr>
                        <tr>
                            <td class="fieldarea" width="150">{$LANG.opsslnew_status}</td>
                            <td>{$status}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea" width="150">{$LANG.opsslnew_creation_date}</td>
                            <td>{$creationDate}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea" width="150">{$LANG.opsslnew_activation_date}</td>
                            <td>{$activationDate}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea" width="150">{$LANG.opsslnew_expiration_date}</td>
                            <td>{$expirationDate}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>
