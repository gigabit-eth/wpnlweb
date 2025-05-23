/**
 * WPNLWeb Admin JavaScript
 *
 * Handles tabbed interface, color picker, live preview,
 * and all interactive functionality for the admin settings page
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/admin/js
 * @since      1.0.0
 */

(function ($) {
  "use strict";

  /**
   * All of the code for your admin-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */

  /**
   * Initialize admin functionality when DOM is ready
   */
  $(document).ready(function () {
    initTabNavigation();
    initColorPicker();
    initCSSEditor();
    initLivePreview();
  });

  /**
   * Initialize tab navigation
   */
  function initTabNavigation() {
    $(".wpnlweb-nav-item").on("click", function (e) {
      e.preventDefault();

      const tabId = $(this).data("tab");

      // Update navigation active state
      $(".wpnlweb-nav-item").removeClass("active");
      $(this).addClass("active");

      // Update tab content visibility
      $(".wpnlweb-tab-content").removeClass("active");
      $("#" + tabId + "-tab").addClass("active");

      // Update URL hash for bookmarking
      window.location.hash = tabId;
    });

    // Check for hash on page load
    const hash = window.location.hash.substring(1);
    if (hash && $('.wpnlweb-nav-item[data-tab="' + hash + '"]').length) {
      $('.wpnlweb-nav-item[data-tab="' + hash + '"]').click();
    }
  }

  /**
   * Initialize color picker functionality
   */
  function initColorPicker() {
    const $colorPicker = $("#wpnlweb_primary_color");
    const $colorText = $("#wpnlweb_primary_color_text");
    const $presetColors = $(".wpnlweb-preset-color");

    // Sync color picker with text input
    $colorPicker.on("change", function () {
      const color = $(this).val();
      $colorText.val(color);
      updatePresetSelection(color);
      updateLivePreviewColor(color);
    });

    // Sync text input with color picker
    $colorText.on("change keyup", function () {
      const color = $(this).val();
      if (isValidHexColor(color)) {
        $colorPicker.val(color);
        updatePresetSelection(color);
        updateLivePreviewColor(color);
      }
    });

    // Handle preset color clicks
    $presetColors.on("click", function (e) {
      e.preventDefault();
      const color = $(this).data("color");
      $colorPicker.val(color);
      $colorText.val(color);
      updatePresetSelection(color);
      updateLivePreviewColor(color);
    });

    /**
     * Update preset color selection
     */
    function updatePresetSelection(selectedColor) {
      $presetColors.removeClass("active");
      $presetColors
        .filter('[data-color="' + selectedColor + '"]')
        .addClass("active");
    }

    /**
     * Update live preview color
     */
    function updateLivePreviewColor(color) {
      $(".wpnlweb-preview-button").css("background-color", color);

      // Update CSS custom property for real-time preview
      $(":root").css("--wpnlweb-primary-color", color);
    }

    /**
     * Validate hex color format
     */
    function isValidHexColor(color) {
      return /^#[0-9A-F]{6}$/i.test(color);
    }

    // Initialize preset selection on load
    updatePresetSelection($colorPicker.val());
  }

  /**
   * Initialize CSS editor functionality
   */
  function initCSSEditor() {
    const $cssEditor = $("#wpnlweb_custom_css");
    const $copyButton = $("#wpnlweb-copy-example");
    const $resetButton = $("#wpnlweb-reset-css");

    // Copy example CSS
    $copyButton.on("click", function () {
      const exampleCSS = `.wpnlweb-search-container { border-radius: 20px; }
.wpnlweb-search-button { background: var(--wpnlweb-primary-color); }
.wpnlweb-search-input { border-color: var(--wpnlweb-primary-color); }`;

      $cssEditor.val(exampleCSS);
      $cssEditor.focus();

      // Show temporary success message
      showTemporaryMessage($copyButton, "✅ Copied!", 2000);
    });

    // Reset CSS
    $resetButton.on("click", function () {
      if (confirm("Are you sure you want to clear all custom CSS?")) {
        $cssEditor.val("");
        $cssEditor.focus();
        showTemporaryMessage($resetButton, "✅ Reset!", 2000);
      }
    });

    // Add syntax highlighting hints (basic)
    $cssEditor.on("input", function () {
      const content = $(this).val();

      // Simple validation
      if (content.includes("{") && !content.includes("}")) {
        $(this).css("border-color", "#f59e0b"); // Warning orange
      } else {
        $(this).css("border-color", ""); // Reset to default
      }
    });

    /**
     * Show temporary message on button
     */
    function showTemporaryMessage($button, message, duration) {
      const originalText = $button.html();
      $button.html(message);
      $button.prop("disabled", true);

      setTimeout(function () {
        $button.html(originalText);
        $button.prop("disabled", false);
      }, duration);
    }
  }

  /**
   * Initialize live preview functionality
   */
  function initLivePreview() {
    const $livePreview = $("#wpnlweb-live-preview");
    const $themeMode = $("#wpnlweb_theme_mode");
    const $refreshButton = $("#wpnlweb-refresh-preview");
    let previewLoaded = false;

    // Update preview when theme mode changes
    $themeMode.on("change", function () {
      if (previewLoaded) {
        updateLivePreview();
      }
    });

    // Handle refresh button click
    $refreshButton.on("click", function (e) {
      e.preventDefault();
      updateLivePreview();
    });

    /**
     * Update live preview via AJAX
     */
    function updateLivePreview() {
      const formData = {
        action: "wpnlweb_preview_shortcode",
        nonce: wpnlweb_admin.nonce,
        theme_mode: $("#wpnlweb_theme_mode").val(),
        primary_color: $("#wpnlweb_primary_color").val(),
        custom_css: $("#wpnlweb_custom_css").val(),
      };

      // Show loading state
      $livePreview.html(
        '<div class="wpnlweb-preview-loading"><span class="wpnlweb-spinner"></span> Loading live preview...</div>'
      );

      // Disable refresh button during load
      $refreshButton.prop("disabled", true);

      $.post(wpnlweb_admin.ajax_url, formData)
        .done(function (response) {
          if (response.success && response.data.html) {
            $livePreview.html(response.data.html);
            previewLoaded = true;

            // Show success message briefly
            showTemporaryMessage($refreshButton, "✅ Updated!", 2000);
          } else {
            $livePreview.html(
              '<div class="wpnlweb-preview-error">❌ Preview failed to load. Please check your settings and try again.</div>'
            );
          }
        })
        .fail(function (xhr, status, error) {
          console.error("Preview AJAX Error:", status, error);
          $livePreview.html(
            '<div class="wpnlweb-preview-error">❌ Connection error. Please check your network and try again.</div>'
          );
        })
        .always(function () {
          // Re-enable refresh button
          $refreshButton.prop("disabled", false);
        });
    }

    // Load preview when switching to live preview tab (first time only)
    $('.wpnlweb-nav-item[data-tab="live-preview"]').on("click", function () {
      if (!previewLoaded) {
        setTimeout(updateLivePreview, 100); // Small delay to ensure tab is visible
      }
    });

    // Auto-refresh when primary color changes (with debouncing)
    let colorChangeTimeout;
    $("#wpnlweb_primary_color, #wpnlweb_primary_color_text").on(
      "change",
      function () {
        if (previewLoaded) {
          clearTimeout(colorChangeTimeout);
          colorChangeTimeout = setTimeout(function () {
            updateLivePreview();
          }, 1000); // Wait 1 second after last change
        }
      }
    );

    /**
     * Show temporary message on button
     */
    function showTemporaryMessage($button, message, duration) {
      const originalText = $button.html();
      $button.html(message);
      $button.prop("disabled", true);

      setTimeout(function () {
        $button.html(originalText);
        $button.prop("disabled", false);
      }, duration);
    }
  }

  /**
   * Handle form submission with validation
   */
  $("#wpnlweb-settings-form").on("submit", function (e) {
    const $form = $(this);
    const $submitButton = $form.find(".wpnlweb-button-primary");

    // Validate color field
    const colorValue = $("#wpnlweb_primary_color_text").val();
    if (colorValue && !/^#[0-9A-F]{6}$/i.test(colorValue)) {
      e.preventDefault();
      alert("Please enter a valid hex color (e.g., #3b82f6)");
      $("#wpnlweb_primary_color_text").focus();
      return;
    }

    // Show saving state
    const originalText = $submitButton.html();
    $submitButton.html("💾 Saving...");
    $submitButton.prop("disabled", true);

    // Re-enable after form submission (in case of errors)
    setTimeout(function () {
      $submitButton.html(originalText);
      $submitButton.prop("disabled", false);
    }, 3000);
  });

  /**
   * Add keyboard shortcuts
   */
  $(document).on("keydown", function (e) {
    // Ctrl/Cmd + S to save
    if ((e.ctrlKey || e.metaKey) && e.which === 83) {
      e.preventDefault();
      $("#wpnlweb-settings-form").submit();
    }

    // Tab navigation with keyboard
    if (e.altKey) {
      switch (e.which) {
        case 49: // Alt + 1
          $('.wpnlweb-nav-item[data-tab="theme"]').click();
          break;
        case 50: // Alt + 2
          $('.wpnlweb-nav-item[data-tab="custom-css"]').click();
          break;
        case 51: // Alt + 3
          $('.wpnlweb-nav-item[data-tab="live-preview"]').click();
          break;
      }
    }
  });

  /**
   * Add smooth animations
   */
  function initAnimations() {
    // Fade in settings groups on tab switch
    $(".wpnlweb-nav-item").on("click", function () {
      const tabId = $(this).data("tab");
      const $tabContent = $("#" + tabId + "-tab");

      $tabContent.css("opacity", "0").animate({ opacity: 1 }, 300);
    });

    // Add hover effects to interactive elements
    $(".wpnlweb-preset-color").hover(
      function () {
        $(this).css("transform", "scale(1.1)");
      },
      function () {
        if (!$(this).hasClass("active")) {
          $(this).css("transform", "scale(1)");
        }
      }
    );
  }

  // Initialize animations
  initAnimations();

  /**
   * Auto-save functionality (optional)
   */
  function initAutoSave() {
    let autoSaveTimeout;

    $("input, textarea, select").on("change", function () {
      clearTimeout(autoSaveTimeout);

      autoSaveTimeout = setTimeout(function () {
        // Show auto-save indicator
        const $indicator = $(
          '<div class="wpnlweb-autosave">💾 Auto-saved</div>'
        );
        $("body").append($indicator);

        setTimeout(function () {
          $indicator.fadeOut(function () {
            $(this).remove();
          });
        }, 2000);
      }, 5000); // Auto-save after 5 seconds of inactivity
    });
  }

  // Uncomment to enable auto-save
  // initAutoSave();
})(jQuery);

/**
 * Additional CSS for loading and error states
 */
jQuery(document).ready(function ($) {
  // Add loading and error styles dynamically
  const styles = `
		<style>
		.wpnlweb-loading {
			text-align: center;
			padding: 40px;
			color: #6b7280;
			font-style: italic;
		}
		
		.wpnlweb-error {
			text-align: center;
			padding: 40px;
			color: #ef4444;
			font-weight: 500;
		}
		
		.wpnlweb-autosave {
			position: fixed;
			top: 32px;
			right: 20px;
			background: #10b981;
			color: white;
			padding: 8px 16px;
			border-radius: 6px;
			font-size: 12px;
			font-weight: 500;
			z-index: 9999;
			box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
		}
		</style>
	`;

  $("head").append(styles);
});
