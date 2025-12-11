<?php get_header(); ?>

<div class="ap-invoice-wrapper">
    <?php while ( have_posts() ) : the_post(); 
        $status = get_post_meta( get_the_ID(), '_ap_invoice_status', true );
        $items = get_post_meta( get_the_ID(), '_ap_line_items', true );
        $total = get_post_meta( get_the_ID(), '_ap_invoice_total', true );
        $due_date = get_post_meta( get_the_ID(), '_ap_invoice_due_date', true );
        
        $status_class = ($status === 'paid') ? 'ap-paid' : 'ap-unpaid';
    ?>
        
        <div class="ap-invoice-paper">
            <header class="ap-inv-header">
                <div class="brand">
                    <?php $logo = get_option('ap_brand_logo'); ?>
                    <?php if($logo): ?><img src="<?php echo esc_url($logo); ?>" alt="Logo"><?php endif; ?>
                    <h1>INVOICE</h1>
                </div>
                <div class="meta">
                    <p><strong>Invoice #:</strong> <?php the_ID(); ?></p>
                    <p><strong>Date:</strong> <?php echo get_the_date(); ?></p>
                    <p><strong>Due Date:</strong> <?php echo esc_html($due_date); ?></p>
                    <div class="status-badge <?php echo $status_class; ?>">
                        <?php echo strtoupper($status ? $status : 'UNPAID'); ?>
                    </div>
                </div>
            </header>

            <div class="ap-inv-body">
                <h3>Bill To:</h3>
                <div class="client-details">
                    <?php echo apply_filters( 'the_content', get_the_content() ); ?>
                </div>

                <table class="ap-inv-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($items): foreach($items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item['desc']); ?></td>
                                <td><?php echo esc_html($item['qty']); ?></td>
                                <td>$<?php echo number_format($item['rate'], 2); ?></td>
                                <td>$<?php echo number_format($item['qty'] * $item['rate'], 2); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3"><strong>Total</strong></td>
                            <td><strong>$<?php echo number_format((float)$total, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="ap-inv-footer">
                <?php if ( $status !== 'paid' ) : ?>
                    <div class="ap-payment-zone">
                        <h3>Payment Method</h3>
                        <p>Secure payment via Stripe.</p>
                        <?php echo do_shortcode('[ap_checkout_button]'); ?>
                    </div>
                <?php else : ?>
                    <div class="ap-thank-you">
                        <h3>Thank You!</h3>
                        <p>This invoice has been fully paid.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endwhile; ?>
</div>

<style>
    .ap-invoice-wrapper { background: #f0f0f0; padding: 40px 0; min-height: 100vh; }
    .ap-invoice-paper { max-width: 800px; margin: 0 auto; background: white; padding: 50px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .ap-inv-header { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
    .ap-inv-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
    .ap-inv-table th, .ap-inv-table td { text-align: left; padding: 15px; border-bottom: 1px solid #eee; }
    .ap-inv-table tfoot td { font-size: 1.2em; border-top: 2px solid #333; }
    .status-badge { padding: 5px 10px; border-radius: 4px; font-weight: bold; text-align: center; margin-top: 10px; }
    .ap-paid { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .ap-unpaid { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    .ap-payment-zone { background: #f8f9fa; padding: 20px; margin-top: 40px; text-align: center; border-radius: 8px; }
</style>

<?php get_footer(); ?>
