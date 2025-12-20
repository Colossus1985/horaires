<script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font("Arial", "normal");
        $size = 9;
        $pageWidth = $pdf->get_width();
        $pageHeight = $pdf->get_height();
        $y = $pageHeight - 30;
        
        // Texte à gauche
        $leftText = "Généré le " . date('d/m/Y à H:i');
        $pdf->text(30, $y, $leftText, $font, $size, array(0.5, 0.5, 0.5));
        
        // Pagination au centre
        $text = "Page {PAGE_NUM} / {PAGE_COUNT}";
        $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
        $x = ($pageWidth - $textWidth) / 2;
        $pdf->text($x, $y, $text, $font, $size, array(0, 0, 0));
        
        // Texte à droite
        $rightText = "Horaires Flo";
        $rightTextWidth = $fontMetrics->getTextWidth($rightText, $font, $size);
        $pdf->text($pageWidth - $rightTextWidth - 30, $y, $rightText, $font, $size, array(0.5, 0.5, 0.5));
        
        // Ligne de séparation
        $pdf->line(30, $pageHeight - 40, $pageWidth - 30, $pageHeight - 40, array(0.7, 0.7, 0.7), 0.5);
    }
</script>
