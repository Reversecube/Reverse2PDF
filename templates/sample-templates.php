<?php
/**
 * Sample Templates for Reverse2PDF
 * Contains pre-built template definitions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get all sample templates
 */
function reverse2pdf_get_sample_templates() {
    return array(
        'business_invoice' => array(
            'name' => __('Business Invoice', 'reverse2pdf'),
            'description' => __('Professional invoice template with company branding', 'reverse2pdf'),
            'category' => 'business',
            'thumbnail' => REVERSE2PDF_PLUGIN_URL . 'assets/images/templates/invoice-thumb.png',
            'data' => reverse2pdf_invoice_template()
        ),
        'contact_form_pdf' => array(
            'name' => __('Contact Form PDF', 'reverse2pdf'),
            'description' => __('PDF generation for contact form submissions', 'reverse2pdf'),
            'category' => 'forms',
            'thumbnail' => REVERSE2PDF_PLUGIN_URL . 'assets/images/templates/contact-thumb.png',
            'data' => reverse2pdf_contact_form_template()
        ),
        'certificate' => array(
            'name' => __('Achievement Certificate', 'reverse2pdf'),
            'description' => __('Professional certificate template for awards', 'reverse2pdf'),
            'category' => 'education',
            'thumbnail' => REVERSE2PDF_PLUGIN_URL . 'assets/images/templates/certificate-thumb.png',
            'data' => reverse2pdf_certificate_template()
        ),
        'report' => array(
            'name' => __('Business Report', 'reverse2pdf'),
            'description' => __('Structured report template with data tables', 'reverse2pdf'),
            'category' => 'business',
            'thumbnail' => REVERSE2PDF_PLUGIN_URL . 'assets/images/templates/report-thumb.png',
            'data' => reverse2pdf_report_template()
        ),
        'letterhead' => array(
            'name' => __('Company Letterhead', 'reverse2pdf'),
            'description' => __('Professional letterhead for business correspondence', 'reverse2pdf'),
            'category' => 'business',
            'thumbnail' => REVERSE2PDF_PLUGIN_URL . 'assets/images/templates/letterhead-thumb.png',
            'data' => reverse2pdf_letterhead_template()
        ),
        'quote' => array(
            'name' => __('Price Quote', 'reverse2pdf'),
            'description' => __('Professional quote template for services', 'reverse2pdf'),
            'category' => 'business',
            'thumbnail' => REVERSE2PDF_PLUGIN_URL . 'assets/images/templates/quote-thumb.png',
            'data' => reverse2pdf_quote_template()
        )
    );
}

/**
 * Business Invoice Template
 */
function reverse2pdf_invoice_template() {
    return array(
        'pages' => array(
            array(
                'width' => 595,
                'height' => 842,
                'elements' => array(
                    // Company logo
                    array(
                        'id' => 'company_logo',
                        'type' => 'image',
                        'x' => 50,
                        'y' => 50,
                        'width' => 120,
                        'height' => 60,
                        'src' => '{company_logo}',
                        'alt' => 'Company Logo'
                    ),
                    // Company info
                    array(
                        'id' => 'company_info',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 120,
                        'width' => 200,
                        'height' => 80,
                        'content' => "{company_name}\n{company_address}\n{company_city}, {company_state} {company_zip}\nPhone: {company_phone}\nEmail: {company_email}",
                        'fontSize' => 10,
                        'fontFamily' => 'Arial',
                        'lineHeight' => 1.4
                    ),
                    // Invoice title
                    array(
                        'id' => 'invoice_title',
                        'type' => 'text',
                        'x' => 400,
                        'y' => 50,
                        'width' => 145,
                        'height' => 30,
                        'content' => 'INVOICE',
                        'fontSize' => 24,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold',
                        'color' => '#333333',
                        'textAlign' => 'right'
                    ),
                    // Invoice number
                    array(
                        'id' => 'invoice_number',
                        'type' => 'text',
                        'x' => 400,
                        'y' => 90,
                        'width' => 145,
                        'height' => 20,
                        'content' => 'Invoice #: {invoice_number}',
                        'fontSize' => 11,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'right'
                    ),
                    // Invoice date
                    array(
                        'id' => 'invoice_date',
                        'type' => 'text',
                        'x' => 400,
                        'y' => 115,
                        'width' => 145,
                        'height' => 20,
                        'content' => 'Date: {invoice_date}',
                        'fontSize' => 11,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'right'
                    ),
                    // Due date
                    array(
                        'id' => 'due_date',
                        'type' => 'text',
                        'x' => 400,
                        'y' => 140,
                        'width' => 145,
                        'height' => 20,
                        'content' => 'Due Date: {due_date}',
                        'fontSize' => 11,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'right',
                        'fontWeight' => 'bold'
                    ),
                    // Bill to
                    array(
                        'id' => 'bill_to_label',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 220,
                        'width' => 60,
                        'height' => 20,
                        'content' => 'Bill To:',
                        'fontSize' => 12,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold'
                    ),
                    // Customer info
                    array(
                        'id' => 'customer_info',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 245,
                        'width' => 250,
                        'height' => 80,
                        'content' => "{customer_name}\n{customer_company}\n{customer_address}\n{customer_city}, {customer_state} {customer_zip}",
                        'fontSize' => 11,
                        'fontFamily' => 'Arial',
                        'lineHeight' => 1.4
                    ),
                    // Items table
                    array(
                        'id' => 'items_table',
                        'type' => 'table',
                        'x' => 50,
                        'y' => 350,
                        'width' => 495,
                        'height' => 200,
                        'hasHeader' => true,
                        'borderWidth' => 1,
                        'cellPadding' => 8,
                        'tableData' => array(
                            array(
                                array('content' => 'Description', 'width' => '50%'),
                                array('content' => 'Qty', 'width' => '10%'),
                                array('content' => 'Unit Price', 'width' => '20%'),
                                array('content' => 'Total', 'width' => '20%')
                            )
                        )
                    ),
                    // Subtotal
                    array(
                        'id' => 'subtotal',
                        'type' => 'text',
                        'x' => 400,
                        'y' => 580,
                        'width' => 145,
                        'height' => 20,
                        'content' => 'Subtotal: {subtotal}',
                        'fontSize' => 11,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'right'
                    ),
                    // Tax
                    array(
                        'id' => 'tax',
                        'type' => 'text',
                        'x' => 400,
                        'y' => 605,
                        'width' => 145,
                        'height' => 20,
                        'content' => 'Tax ({tax_rate}%): {tax_amount}',
                        'fontSize' => 11,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'right'
                    ),
                                        // Total
                    array(
                        'id' => 'total',
                        'type' => 'text',
                        'x' => 400,
                        'y' => 630,
                        'width' => 145,
                        'height' => 25,
                        'content' => 'Total: {total_amount}',
                        'fontSize' => 14,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold',
                        'textAlign' => 'right',
                        'color' => '#d32f2f'
                    ),
                    // Payment terms
                    array(
                        'id' => 'payment_terms',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 680,
                        'width' => 495,
                        'height' => 60,
                        'content' => "Payment Terms:\n{payment_terms}\n\nThank you for your business!",
                        'fontSize' => 10,
                        'fontFamily' => 'Arial',
                        'lineHeight' => 1.4
                    ),
                    // Footer line
                    array(
                        'id' => 'footer_line',
                        'type' => 'line',
                        'x' => 50,
                        'y' => 760,
                        'width' => 495,
                        'height' => 1,
                        'color' => '#cccccc',
                        'thickness' => 1
                    ),
                    // Footer text
                    array(
                        'id' => 'footer_text',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 770,
                        'width' => 495,
                        'height' => 40,
                        'content' => "{company_name} • {company_website} • Invoice generated on {current_date}",
                        'fontSize' => 8,
                        'fontFamily' => 'Arial',
                        'color' => '#666666',
                        'textAlign' => 'center'
                    )
                )
            )
        )
    );
}

/**
 * Contact Form PDF Template
 */
function reverse2pdf_contact_form_template() {
    return array(
        'pages' => array(
            array(
                'width' => 595,
                'height' => 842,
                'elements' => array(
                    // Header
                    array(
                        'id' => 'header',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 50,
                        'width' => 495,
                        'height' => 40,
                        'content' => 'CONTACT FORM SUBMISSION',
                        'fontSize' => 20,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold',
                        'textAlign' => 'center',
                        'color' => '#333333'
                    ),
                    // Submission details
                    array(
                        'id' => 'submission_info',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 100,
                        'width' => 495,
                        'height' => 40,
                        'content' => "Submitted on: {submission_date} at {submission_time}\nFrom IP: {user_ip}",
                        'fontSize' => 10,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'center',
                        'color' => '#666666',
                        'lineHeight' => 1.4
                    ),
                    // Name field
                    array(
                        'id' => 'name_label',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 160,
                        'width' => 80,
                        'height' => 20,
                        'content' => 'Name:',
                        'fontSize' => 12,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold'
                    ),
                    array(
                        'id' => 'name_value',
                        'type' => 'text',
                        'x' => 140,
                        'y' => 160,
                        'width' => 405,
                        'height' => 20,
                        'content' => '{your-name}',
                        'fontSize' => 12,
                        'fontFamily' => 'Arial'
                    ),
                    // Email field
                    array(
                        'id' => 'email_label',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 190,
                        'width' => 80,
                        'height' => 20,
                        'content' => 'Email:',
                        'fontSize' => 12,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold'
                    ),
                    array(
                        'id' => 'email_value',
                        'type' => 'text',
                        'x' => 140,
                        'y' => 190,
                        'width' => 405,
                        'height' => 20,
                        'content' => '{your-email}',
                        'fontSize' => 12,
                        'fontFamily' => 'Arial'
                    ),
                    // Phone field
                    array(
                        'id' => 'phone_label',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 220,
                        'width' => 80,
                        'height' => 20,
                        'content' => 'Phone:',
                        'fontSize' => 12,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold'
                    ),
                    array(
                        'id' => 'phone_value',
                        'type' => 'text',
                        'x' => 140,
                        'y' => 220,
                        'width' => 405,
                        'height' => 20,
                        'content' => '{your-phone}',
                        'fontSize' => 12,
                        'fontFamily' => 'Arial'
                    ),
                    // Subject field
                    array(
                        'id' => 'subject_label',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 250,
                        'width' => 80,
                        'height' => 20,
                        'content' => 'Subject:',
                        'fontSize' => 12,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold'
                    ),
                    array(
                        'id' => 'subject_value',
                        'type' => 'text',
                        'x' => 140,
                        'y' => 250,
                        'width' => 405,
                        'height' => 20,
                        'content' => '{your-subject}',
                        'fontSize' => 12,
                        'fontFamily' => 'Arial'
                    ),
                    // Message field
                    array(
                        'id' => 'message_label',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 290,
                        'width' => 80,
                        'height' => 20,
                        'content' => 'Message:',
                        'fontSize' => 12,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold'
                    ),
                    array(
                        'id' => 'message_value',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 320,
                        'width' => 495,
                        'height' => 200,
                        'content' => '{your-message}',
                        'fontSize' => 12,
                        'fontFamily' => 'Arial',
                        'lineHeight' => 1.5
                    ),
                    // Company info footer
                    array(
                        'id' => 'company_footer',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 750,
                        'width' => 495,
                        'height' => 40,
                        'content' => "{site_name} • {site_url}\nThis form submission was generated automatically on {current_datetime}",
                        'fontSize' => 9,
                        'fontFamily' => 'Arial',
                        'color' => '#666666',
                        'textAlign' => 'center',
                        'lineHeight' => 1.4
                    )
                )
            )
        )
    );
}

/**
 * Certificate Template
 */
function reverse2pdf_certificate_template() {
    return array(
        'pages' => array(
            array(
                'width' => 842,
                'height' => 595,  // Landscape
                'elements' => array(
                    // Decorative border
                    array(
                        'id' => 'outer_border',
                        'type' => 'rectangle',
                        'x' => 20,
                        'y' => 20,
                        'width' => 802,
                        'height' => 555,
                        'fillColor' => 'transparent',
                        'borderColor' => '#8B4513',
                        'borderWidth' => 4
                    ),
                    array(
                        'id' => 'inner_border',
                        'type' => 'rectangle',
                        'x' => 30,
                        'y' => 30,
                        'width' => 782,
                        'height' => 535,
                        'fillColor' => 'transparent',
                        'borderColor' => '#DAA520',
                        'borderWidth' => 2
                    ),
                    // Certificate title
                    array(
                        'id' => 'certificate_title',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 80,
                        'width' => 742,
                        'height' => 50,
                        'content' => 'CERTIFICATE OF ACHIEVEMENT',
                        'fontSize' => 32,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold',
                        'textAlign' => 'center',
                        'color' => '#8B4513'
                    ),
                    // Awarded to text
                    array(
                        'id' => 'awarded_to',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 180,
                        'width' => 742,
                        'height' => 30,
                        'content' => 'This is to certify that',
                        'fontSize' => 18,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'center',
                        'color' => '#333333'
                    ),
                    // Recipient name
                    array(
                        'id' => 'recipient_name',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 240,
                        'width' => 742,
                        'height' => 50,
                        'content' => '{recipient_name}',
                        'fontSize' => 36,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold',
                        'textAlign' => 'center',
                        'color' => '#8B4513',
                        'textDecoration' => 'underline'
                    ),
                    // Achievement description
                    array(
                        'id' => 'achievement_text',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 320,
                        'width' => 742,
                        'height' => 60,
                        'content' => "has successfully completed the course\n\n{course_name}",
                        'fontSize' => 16,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'center',
                        'color' => '#333333',
                        'lineHeight' => 1.5
                    ),
                    // Date
                    array(
                        'id' => 'certificate_date',
                        'type' => 'text',
                        'x' => 100,
                        'y' => 450,
                        'width' => 200,
                        'height' => 30,
                        'content' => "Date: {completion_date}",
                        'fontSize' => 12,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'center'
                    ),
                    // Signature line
                    array(
                        'id' => 'signature_line',
                        'type' => 'line',
                        'x' => 542,
                        'y' => 470,
                        'width' => 200,
                        'height' => 1,
                        'color' => '#333333',
                        'thickness' => 1
                    ),
                    array(
                        'id' => 'signature_label',
                        'type' => 'text',
                        'x' => 542,
                        'y' => 480,
                        'width' => 200,
                        'height' => 20,
                        'content' => '{instructor_name}\nInstructor',
                        'fontSize' => 12,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'center',
                        'lineHeight' => 1.2
                    )
                )
            )
        )
    );
}

/**
 * Business Report Template
 */
function reverse2pdf_report_template() {
    return array(
        'pages' => array(
            array(
                'width' => 595,
                'height' => 842,
                'elements' => array(
                    // Header section
                    array(
                        'id' => 'report_header',
                        'type' => 'rectangle',
                        'x' => 0,
                        'y' => 0,
                        'width' => 595,
                        'height' => 80,
                        'fillColor' => '#f8f9fa',
                        'borderColor' => '#dee2e6',
                        'borderWidth' => 1
                    ),
                    array(
                        'id' => 'report_title',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 20,
                        'width' => 495,
                        'height' => 40,
                        'content' => '{report_title}',
                        'fontSize' => 24,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold',
                        'textAlign' => 'center'
                    ),
                    // Report info
                    array(
                        'id' => 'report_info',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 100,
                        'width' => 495,
                        'height' => 40,
                        'content' => "Report Period: {report_period} • Generated: {generation_date}\nPrepared by: {prepared_by}",
                        'fontSize' => 10,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'center',
                        'lineHeight' => 1.4,
                        'color' => '#666666'
                    ),
                    // Executive Summary
                    array(
                        'id' => 'summary_header',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 160,
                        'width' => 495,
                        'height' => 25,
                        'content' => 'EXECUTIVE SUMMARY',
                        'fontSize' => 16,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold',
                        'backgroundColor' => '#e9ecef',
                        'padding' => '8px'
                    ),
                    array(
                        'id' => 'summary_content',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 190,
                        'width' => 495,
                        'height' => 80,
                        'content' => '{executive_summary}',
                        'fontSize' => 11,
                        'fontFamily' => 'Arial',
                        'lineHeight' => 1.4
                    ),
                    // Key Metrics Table
                    array(
                        'id' => 'metrics_header',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 290,
                        'width' => 495,
                        'height' => 25,
                        'content' => 'KEY METRICS',
                        'fontSize' => 16,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold',
                        'backgroundColor' => '#e9ecef',
                        'padding' => '8px'
                    ),
                    array(
                        'id' => 'metrics_table',
                        'type' => 'table',
                        'x' => 50,
                        'y' => 320,
                        'width' => 495,
                        'height' => 120,
                        'hasHeader' => true,
                        'tableData' => array(
                            array(
                                array('content' => 'Metric'),
                                array('content' => 'Current Period'),
                                array('content' => 'Previous Period'),
                                array('content' => 'Change')
                            )
                        )
                    ),
                    // Analysis section
                    array(
                        'id' => 'analysis_header',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 460,
                        'width' => 495,
                        'height' => 25,
                        'content' => 'DETAILED ANALYSIS',
                        'fontSize' => 16,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold',
                        'backgroundColor' => '#e9ecef',
                        'padding' => '8px'
                    ),
                    array(
                        'id' => 'analysis_content',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 490,
                        'width' => 495,
                        'height' => 120,
                        'content' => '{detailed_analysis}',
                        'fontSize' => 11,
                        'fontFamily' => 'Arial',
                        'lineHeight' => 1.4
                    ),
                    // Recommendations
                    array(
                        'id' => 'recommendations_header',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 630,
                        'width' => 495,
                        'height' => 25,
                        'content' => 'RECOMMENDATIONS',
                        'fontSize' => 16,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold',
                        'backgroundColor' => '#e9ecef',
                        'padding' => '8px'
                    ),
                    array(
                        'id' => 'recommendations_content',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 660,
                        'width' => 495,
                        'height' => 80,
                        'content' => '{recommendations}',
                        'fontSize' => 11,
                        'fontFamily' => 'Arial',
                        'lineHeight' => 1.4
                    ),
                    // Footer
                    array(
                        'id' => 'report_footer',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 790,
                        'width' => 495,
                        'height' => 30,
                        'content' => "Page 1 of 1 • {company_name} • Confidential",
                        'fontSize' => 9,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'center',
                        'color' => '#666666'
                    )
                )
            )
        )
    );
}

/**
 * Letterhead Template
 */
function reverse2pdf_letterhead_template() {
    return array(
        'pages' => array(
            array(
                'width' => 595,
                'height' => 842,
                'elements' => array(
                    // Header background
                    array(
                        'id' => 'header_bg',
                        'type' => 'rectangle',
                        'x' => 0,
                        'y' => 0,
                        'width' => 595,
                        'height' => 120,
                        'fillColor' => '#f8f9fa',
                        'borderColor' => 'transparent'
                    ),
                    // Company logo
                    array(
                        'id' => 'company_logo',
                        'type' => 'image',
                        'x' => 50,
                        'y' => 30,
                        'width' => 120,
                        'height' => 60,
                        'src' => '{company_logo}',
                        'alt' => 'Company Logo'
                    ),
                    // Company name
                    array(
                        'id' => 'company_name',
                        'type' => 'text',
                        'x' => 200,
                        'y' => 30,
                        'width' => 345,
                        'height' => 30,
                        'content' => '{company_name}',
                        'fontSize' => 24,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold',
                        'color' => '#333333'
                    ),
                    // Company details
                    array(
                        'id' => 'company_details',
                        'type' => 'text',
                        'x' => 200,
                        'y' => 65,
                        'width' => 345,
                        'height' => 40,
                        'content' => "{company_address}, {company_city}, {company_state} {company_zip}\nPhone: {company_phone} • Email: {company_email} • Web: {company_website}",
                        'fontSize' => 10,
                        'fontFamily' => 'Arial',
                        'color' => '#666666',
                        'lineHeight' => 1.4
                    ),
                    // Date
                    array(
                        'id' => 'letter_date',
                        'type' => 'text',
                        'x' => 400,
                        'y' => 150,
                        'width' => 145,
                        'height' => 20,
                        'content' => '{letter_date}',
                        'fontSize' => 11,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'right'
                    ),
                    // Recipient address
                    array(
                        'id' => 'recipient_address',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 200,
                        'width' => 300,
                        'height' => 100,
                        'content' => "{recipient_name}\n{recipient_title}\n{recipient_company}\n{recipient_address}\n{recipient_city}, {recipient_state} {recipient_zip}",
                        'fontSize' => 11,
                        'fontFamily' => 'Arial',
                        'lineHeight' => 1.4
                    ),
                    // Salutation
                    array(
                        'id' => 'salutation',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 330,
                        'width' => 495,
                        'height' => 25,
                        'content' => 'Dear {recipient_name},',
                        'fontSize' => 12,
                        'fontFamily' => 'Arial'
                    ),
                    // Letter body
                    array(
                        'id' => 'letter_body',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 370,
                        'width' => 495,
                        'height' => 300,
                        'content' => '{letter_content}',
                        'fontSize' => 12,
                        'fontFamily' => 'Arial',
                        'lineHeight' => 1.5
                    ),
                    // Closing
                    array(
                        'id' => 'closing',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 690,
                        'width' => 200,
                        'height' => 80,
                        'content' => "Sincerely,\n\n\n{sender_name}\n{sender_title}",
                        'fontSize' => 12,
                        'fontFamily' => 'Arial',
                        'lineHeight' => 1.4
                    ),
                    // Footer line
                    array(
                        'id' => 'footer_line',
                        'type' => 'line',
                        'x' => 0,
                        'y' => 800,
                        'width' => 595,
                        'height' => 1,
                        'color' => '#cccccc',
                        'thickness' => 1
                    ),
                    // Footer text
                    array(
                        'id' => 'footer_text',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 810,
                        'width' => 495,
                        'height' => 20,
                        'content' => "{company_name} • {company_website}",
                        'fontSize' => 9,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'center',
                        'color' => '#666666'
                    )
                )
            )
        )
    );
}

/**
 * Quote Template
 */
function reverse2pdf_quote_template() {
    return array(
        'pages' => array(
            array(
                'width' => 595,
                'height' => 842,
                'elements' => array(
                    // Quote title
                    array(
                        'id' => 'quote_title',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 50,
                        'width' => 495,
                        'height' => 40,
                        'content' => 'PRICE QUOTE',
                        'fontSize' => 24,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold',
                        'textAlign' => 'center',
                        'color' => '#2196F3'
                    ),
                    // Quote details
                    array(
                        'id' => 'quote_details',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 100,
                        'width' => 495,
                        'height' => 60,
                        'content' => "Quote #: {quote_number}\nDate: {quote_date}\nValid Until: {quote_expiry}\n\nPrepared for: {client_name}",
                        'fontSize' => 11,
                        'fontFamily' => 'Arial',
                        'lineHeight' => 1.4
                    ),
                    // Services table
                    array(
                        'id' => 'services_table',
                        'type' => 'table',
                        'x' => 50,
                        'y' => 200,
                        'width' => 495,
                        'height' => 200,
                        'hasHeader' => true,
                        'tableData' => array(
                            array(
                                array('content' => 'Service Description'),
                                array('content' => 'Quantity'),
                                array('content' => 'Rate'),
                                array('content' => 'Amount')
                            )
                        )
                    ),
                    // Terms and conditions
                    array(
                        'id' => 'terms_title',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 450,
                        'width' => 495,
                        'height' => 25,
                        'content' => 'Terms & Conditions',
                        'fontSize' => 14,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'bold'
                    ),
                    array(
                        'id' => 'terms_content',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 480,
                        'width' => 495,
                        'height' => 100,
                        'content' => '{terms_conditions}',
                        'fontSize' => 10,
                        'fontFamily' => 'Arial',
                        'lineHeight' => 1.4
                    ),
                    // Total amount
                    array(
                        'id' => 'total_amount',
                        'type' => 'text',
                        'x' => 300,
                        'y' => 600,
                        'width' => 245,
                        'height' => 60,
                        'content' => "Subtotal: {subtotal}\nTax ({tax_rate}%): {tax_amount}\n\nTOTAL: {total_amount}",
                        'fontSize' => 12,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'right',
                        'lineHeight' => 1.4,
                        'backgroundColor' => '#f8f9fa',
                        'padding' => '10px'
                    ),
                    // Contact info
                    array(
                        'id' => 'contact_info',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 700,
                        'width' => 495,
                        'height' => 60,
                        'content' => "Questions about this quote?\nContact: {contact_person} • Phone: {contact_phone} • Email: {contact_email}",
                        'fontSize' => 11,
                        'fontFamily' => 'Arial',
                        'textAlign' => 'center',
                        'lineHeight' => 1.4,
                        'color' => '#666666'
                    )
                )
            )
        )
    );
}
?>
