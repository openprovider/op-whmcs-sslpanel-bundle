<?php if (!empty($view['products'])): ?>
    <table width="100%" cellspacing="1" cellpadding="3" border="0" class="datatable">
        <tbody>
        <tr>
            <th><?php echo 'id'; ?></th>
            <th><?php echo 'name'; ?></th>
            <th><?php echo 'brandName'; ?></th>
            <th><?php echo 'price'; ?></th>
        </tr>
        <?php foreach ($view['products'] as $item): ?>
            <tr>
                <td><?php echo $item['id']?></td>
                <td><?php echo $item['name']?></td>
                <td><?php echo $item['brandName']?></td>
                <td><?php echo $item['price']?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php echo 'addon list pages'; ?> :
    <?php foreach ($view['pages'] as $p): ?>
        <a href="<?php echo $view['global']['mod_action_url'] ?>&user=<?php echo $view['user']['user'] ?>&acc=<?php echo $view['user']['acc'] ?>&page=<?php echo $p ?>"><?php if ($view['current_page'] == $p): ?>
                <strong><?php echo $p ?></strong><?php else : ?><?php echo $p ?><?php endif; ?></a>
    <?php endforeach; ?>

<?php endif; ?>
