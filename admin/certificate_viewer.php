<?php
/**
 * Classic Certificate Viewer Modal Component
 * 
 * This file contains the improved modal used to view patient certificates
 * in the medical administration system, with a classic design and proper image fitting.
 */
?>
<!-- Certificate Viewer Modal -->
<div class="modal fade" id="certificateModal" tabindex="-1" role="dialog" aria-labelledby="certificateModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="certificateModalLabel">
                    <i class="fas fa-file-medical mr-2"></i>Patient Medical Certificate
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="certificate-header py-2 px-3 text-center bg-light border-bottom">
                <small class="text-muted font-italic">Medical System - Official Patient Document</small>
            </div>
            <div class="modal-body p-0">
                <div class="certificate-container">
                    <iframe id="certificateFrame" class="certificate-frame"></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <a id="downloadCertificate" href="#" class="btn btn-success" download>
                    <i class="fas fa-download mr-1"></i> Download
                </a>
                <a id="printCertificate" href="#" class="btn btn-info" onclick="printCertificate(); return false;">
                    <i class="fas fa-print mr-1"></i> Print
                </a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Certificate Viewer Styles -->
<style>
    /* Modal enhancements */
    .modal-content {
        border: none;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    /* Classic header styling */
    .modal-header.bg-primary {
        background: linear-gradient(to right, #305a9c, #1c4786) !important;
        border-bottom: 3px solid #0f3871;
    }

    .modal-title {
        font-family: 'Times New Roman', Times, serif;
        letter-spacing: 0.5px;
        font-weight: 500;
    }

    /* Classic container styling */
    .certificate-container {
        height: 75vh;
        overflow: hidden;
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.05);
    }

    .certificate-frame {
        width: 100%;
        height: 100%;
        border: none;
    }

    /* Custom styles for PDF objects inside the iframe */
    .certificate-frame object,
    .certificate-frame embed,
    .certificate-frame img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    /* Classic footer styling */
    .modal-footer {
        background-color: #f5f5f5;
        border-top: 1px solid #ddd;
    }

    /* Button styling */
    .btn {
        font-family: 'Arial', sans-serif;
        font-size: 14px;
        padding: 6px 15px;
    }

    .btn-success {
        background: linear-gradient(to bottom, #5cb85c, #4cae4c);
        border-color: #4cae4c;
    }

    .btn-info {
        background: linear-gradient(to bottom, #5bc0de, #46b8da);
        border-color: #46b8da;
    }

    .btn-secondary {
        background: linear-gradient(to bottom, #6c757d, #5a6268);
        border-color: #5a6268;
    }
</style>

<!-- Certificate Viewer JavaScript -->
<script>
    /**
     * Opens the certificate viewer modal with the specified document URL
     * 
     * @param {string} url - The URL of the certificate to view
     * @return {boolean} - Returns false to prevent default link action
     */
    function viewCertificate(url) {
        // Set the source for the iframe
        $('#certificateFrame').attr('src', url);

        // Update download link
        $('#downloadCertificate').attr('href', url);

        // Store the URL for printing
        $('#certificateModal').data('certificate-url', url);

        // Show the modal
        $('#certificateModal').modal('show');

        return false;
    }

    /**
     * Handles printing of the certificate
     */
    function printCertificate() {
        const iframe = document.getElementById('certificateFrame');

        try {
            // Try to use the iframe's print function first
            iframe.contentWindow.print();
        } catch (e) {
            // Fallback: open in new window and print
            const url = $('#certificateModal').data('certificate-url');
            const printWindow = window.open(url, 'Print Certificate', 'height=600,width=800');

            if (printWindow) {
                printWindow.addEventListener('load', function () {
                    printWindow.print();
                    // Close after printing (optional)
                    // printWindow.close();
                }, true);
            } else {
                alert('Please allow popups to print the certificate');
            }
        }
    }

    // Initialize the certificate viewer when document is ready
    $(document).ready(function () {
        // Handle iframe load event to fix image fitting
        $('#certificateFrame').on('load', function () {
            const frameDoc = this.contentDocument || this.contentWindow.document;
            const fileType = getFileType($(this).attr('src'));

            if (fileType === 'image') {
                try {
                    // For images, inject custom CSS to ensure proper fitting
                    const imgElement = frameDoc.querySelector('img');
                    if (imgElement) {
                        const style = document.createElement('style');
                        style.textContent = `
                            html, body {
                                margin: 0;
                                padding: 0;
                                height: 100%;
                                width: 100%;
                                overflow: hidden;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                background-color: #f9f9f9;
                            }
                            img {
                                max-width: 100%;
                                max-height: 100%;
                                object-fit: contain;
                                display: block;
                            }
                        `;
                        frameDoc.head.appendChild(style);
                    }
                } catch (e) {
                    console.error('Error optimizing image display:', e);
                }
            } else if (fileType === 'pdf') {
                // For PDFs, ensure they fit properly
                try {
                    const style = document.createElement('style');
                    style.textContent = `
                        html, body {
                            margin: 0;
                            padding: 0;
                            height: 100%;
                            width: 100%;
                            overflow: hidden;
                        }
                        object, embed {
                            width: 100%;
                            height: 100%;
                        }
                    `;
                    frameDoc.head.appendChild(style);
                } catch (e) {
                    console.error('Error optimizing PDF display:', e);
                }
            }
        });

        // Add file type detection
        function getFileType(url) {
            if (!url) return 'unknown';

            const fileExt = url.split('.').pop().toLowerCase();
            if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(fileExt)) {
                return 'image';
            } else if (fileExt === 'pdf') {
                return 'pdf';
            } else {
                return 'other';
            }
        }
    });
</script>