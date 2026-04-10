<?php

function renderAdminTable(array $headers, array $rows, callable $rowRenderer): void
{
    ?>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 admin-data-table">
                <thead>
                    <tr>
                        <?php foreach ($headers as $header) : ?>
                            <th scope="col"><?= adminText($header) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)) : ?>
                        <tr>
                            <td colspan="<?= count($headers) ?>" class="text-center py-4 text-muted">Không có dữ liệu phù hợp</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($rows as $row) : ?>
                            <?= $rowRenderer($row) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
