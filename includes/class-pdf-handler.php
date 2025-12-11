<?php
/**
 * Handles PDF Generation for Invoices and Contracts.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_PDF_Handler {

    public function init() {
        add_action( 'template_redirect', array( $this, 'maybe_generate_pdf' ) );
    }

    public function maybe_generate_pdf() {
        if ( ! isset( $_GET['download_pdf'] ) || ! is_singular( array( 'ap_invoice', 'ap_contract' ) ) ) {
            return;
        }

        $post_id = get_the_ID();

        // Basic Security: Check if user can view this
        if ( post_password_required( $post_id ) ) {
            wp_die( 'Password Protected: Please enter the password on the page before downloading.', 'Password Required', array( 'response' => 403 ) );
        }

        // Capture HTML
        ob_start();
        $this->render_pdf_html( $post_id );
        $html = ob_get_clean();

        // If DomPDF is available, use it
        if ( class_exists( 'Dompdf\Dompdf' ) ) {
            // Setup DomPDF
            $options = new \Dompdf\Options();
            $options->set( 'isRemoteEnabled', true );
            $options->set( 'isHtml5ParserEnabled', true );

            $dompdf = new \Dompdf\Dompdf( $options );
            $dompdf->loadHtml( $html );
            $dompdf->setPaper( 'A4', 'portrait' );
            $dompdf->render();

            // Stream the file
            $filename = 'document-' . $post_id . '.pdf';
            $dompdf->stream( $filename, array( 'Attachment' => true ) );
            exit;
        } else {
            // Fallback: Render HTML with a print script
            echo $html;
            echo '<script>window.print();</script>';
            exit;
        }
    }

    private function render_pdf_html( $post_id ) {
        $post = get_post( $post_id );
        setup_postdata( $post );

        // We render a simplified version of the content
        // This relies on the single templates being clean or we reconstruct it here.
        // For consistency, let's load a minimal header/footer and the specific template part.

        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php the_title(); ?></title>
            <style>
                body { font-family: Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.5; color: #333; }
                .ap-invoice-paper, .ap-contract-paper { max-width: 100%; padding: 20px; }
                h1 { margin-bottom: 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { text-align: left; padding: 10px; border-bottom: 1px solid #eee; }
                .text-right { text-align: right; }
                .status-badge { display: inline-block; padding: 5px 10px; border: 1px solid #ccc; background: #eee; }
                /* Add more specific print styles here */
                img { max-width: 200px; height: auto; }
            </style>
        </head>
        <body>
            <?php
            // We need to essentially replicate the view logic or include the template part.
            // Including the theme file might include get_header() which we don't want.
            // So we reconstruct the data display here for PDF purity.

            if ( get_post_type($post_id) === 'ap_invoice' ) {
                $this->render_invoice_body( $post_id );
            } elseif ( get_post_type($post_id) === 'ap_contract' ) {
                $this->render_contract_body( $post_id );
            }
            ?>
        </body>
        </html>
        <?php
    }

    private function render_invoice_body( $post_id ) {
        $items = get_post_meta( $post_id, '_ap_line_items', true );
        $total = get_post_meta( $post_id, '_ap_invoice_total', true );
        $due   = get_post_meta( $post_id, '_ap_invoice_due_date', true );
        $logo  = get_option('ap_brand_logo');
        ?>
        <div style="margin-bottom: 40px;">
            <?php if($logo): ?><img src="<?php echo esc_url($logo); ?>" style="float:left; margin-right: 20px;"><?php endif; ?>
            <div style="float:right; text-align:right;">
                <h1>INVOICE</h1>
                <p>#<?php echo $post_id; ?><br>Due: <?php echo esc_html($due); ?></p>
            </div>
            <div style="clear:both;"></div>
        </div>

        <div style="margin-bottom: 30px;">
            <strong>Bill To:</strong><br>
            <?php echo wpautop( get_post_field( 'post_content', $post_id ) ); ?>
        </div>

        <table>
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
                    <td colspan="3" class="text-right"><strong>Total</strong></td>
                    <td><strong>$<?php echo number_format((float)$total, 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
        <?php
    }

    private function render_contract_body( $post_id ) {
        // Contracts usually just need the content and the signature info
        echo '<h1>' . get_the_title($post_id) . '</h1>';
        echo wpautop( get_post_field( 'post_content', $post_id ) );

        $signature = get_post_meta( $post_id, '_ap_signature', true );
        if ( $signature ) {
            echo '<div style="margin-top: 50px; border-top: 1px solid #ccc; padding-top: 20px;">';
            echo '<p><strong>Signed By Client:</strong></p>';
            echo '<img src="' . esc_attr($signature) . '" style="max-width: 300px;">';
            echo '<p>Date: ' . get_the_modified_date() . '</p>';
            echo '</div>';
        }
    }
}
