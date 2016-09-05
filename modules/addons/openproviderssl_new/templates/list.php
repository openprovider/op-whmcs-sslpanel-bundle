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

<?php endif; ?>
