/**
 * WPNLWeb Shortcode JavaScript
 *
 * Handles AJAX search functionality for the [wpnlweb] shortcode
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/public/js
 * @since      1.0.0
 */

(function ($) {
  "use strict";

  /**
   * Initialize shortcode functionality when document is ready
   */
  $(document).ready(function () {
    initializeWPNLWebShortcodes();
  });

  /**
   * Initialize all WPNLWeb shortcode forms on the page
   */
  function initializeWPNLWebShortcodes() {
    // Check if we have any shortcode data
    if (typeof window.wpnlweb_data === "undefined") {
      return;
    }

    // Initialize each shortcode form
    for (let formId in window.wpnlweb_data) {
      if (window.wpnlweb_data.hasOwnProperty(formId)) {
        initializeShortcodeForm(formId, window.wpnlweb_data[formId]);
      }
    }
  }

  /**
   * Initialize individual shortcode form
   */
  function initializeShortcodeForm(formId, config) {
    const form = $("#" + formId);
    const resultsContainer = $("#" + config.results_id);
    const loadingIndicator = form.find(".wpnlweb-loading");
    const searchButton = form.find(".wpnlweb-search-button");
    const searchInput = form.find(".wpnlweb-search-input");

    if (form.length === 0) {
      console.warn("WPNLWeb: Form not found for ID:", formId);
      return;
    }

    // Handle form submission
    form.on("submit", function (e) {
      e.preventDefault();

      const question = searchInput.val().trim();

      if (question === "") {
        displayError(resultsContainer, "Please enter a question");
        return;
      }

      performSearch(
        question,
        config,
        form,
        resultsContainer,
        loadingIndicator,
        searchButton
      );
    });

    // Handle Enter key in search input
    searchInput.on("keypress", function (e) {
      if (e.which === 13) {
        form.submit();
      }
    });

    // Auto-focus on search input when shortcode is in view
    if (isElementInViewport(form[0])) {
      searchInput.focus();
    }
  }

  /**
   * Perform AJAX search request
   */
  function performSearch(
    question,
    config,
    form,
    resultsContainer,
    loadingIndicator,
    searchButton
  ) {
    // Show loading state
    showLoadingState(loadingIndicator, searchButton, true);

    // Hide previous results
    if (config.show_results) {
      resultsContainer.hide();
    }

    // Prepare AJAX data
    const ajaxData = {
      action: "wpnlweb_search",
      question: question,
      max_results: config.max_results,
      wpnlweb_nonce: config.nonce,
    };

    // Perform AJAX request
    $.ajax({
      url: config.ajax_url,
      type: "POST",
      data: ajaxData,
      timeout: 30000, // 30 second timeout
      success: function (response) {
        handleSearchSuccess(response, config, resultsContainer);
      },
      error: function (xhr, status, error) {
        handleSearchError(xhr, status, error, resultsContainer);
      },
      complete: function () {
        showLoadingState(loadingIndicator, searchButton, false);
      },
    });
  }

  /**
   * Handle successful search response
   */
  function handleSearchSuccess(response, config, resultsContainer) {
    if (!config.show_results) {
      return;
    }

    if (response.success && response.data) {
      const resultsContent = resultsContainer.find(".wpnlweb-results-content");
      resultsContent.html(response.data.html);

      // Update results title with count
      const resultsTitle = resultsContainer.find(".wpnlweb-results-title");
      const count = response.data.count || 0;
      resultsTitle.text("Search Results (" + count + " found)");

      // Show results container
      resultsContainer.slideDown();

      // Scroll to results if needed
      scrollToResults(resultsContainer);
    } else {
      displayError(resultsContainer, response.data?.message || "Search failed");
    }
  }

  /**
   * Handle search error response
   */
  function handleSearchError(xhr, status, error, resultsContainer) {
    let errorMessage = "Search request failed";

    if (status === "timeout") {
      errorMessage = "Search request timed out. Please try again.";
    } else if (
      xhr.responseJSON &&
      xhr.responseJSON.data &&
      xhr.responseJSON.data.message
    ) {
      errorMessage = xhr.responseJSON.data.message;
    } else if (status === "error" && error) {
      errorMessage = "Network error: " + error;
    }

    displayError(resultsContainer, errorMessage);
    console.error("WPNLWeb search error:", { xhr, status, error });
  }

  /**
   * Display error message
   */
  function displayError(resultsContainer, message) {
    if (resultsContainer.length === 0) {
      alert("Error: " + message);
      return;
    }

    const errorHtml =
      '<div class="wpnlweb-error">' + escapeHtml(message) + "</div>";
    const resultsContent = resultsContainer.find(".wpnlweb-results-content");

    resultsContent.html(errorHtml);
    resultsContainer.slideDown();

    scrollToResults(resultsContainer);
  }

  /**
   * Show/hide loading state
   */
  function showLoadingState(loadingIndicator, searchButton, isLoading) {
    if (isLoading) {
      loadingIndicator.show();
      searchButton.prop("disabled", true).text("Searching...");
    } else {
      loadingIndicator.hide();
      searchButton
        .prop("disabled", false)
        .text(searchButton.data("original-text") || "Search");
    }
  }

  /**
   * Scroll to results if they're not in viewport
   */
  function scrollToResults(resultsContainer) {
    if (!isElementInViewport(resultsContainer[0])) {
      $("html, body").animate(
        {
          scrollTop: resultsContainer.offset().top - 20,
        },
        500
      );
    }
  }

  /**
   * Check if element is in viewport
   */
  function isElementInViewport(element) {
    if (!element) return false;

    const rect = element.getBoundingClientRect();
    return (
      rect.top >= 0 &&
      rect.left >= 0 &&
      rect.bottom <=
        (window.innerHeight || document.documentElement.clientHeight) &&
      rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
  }

  /**
   * Escape HTML to prevent XSS
   */
  function escapeHtml(text) {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text.replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  }

  /**
   * Store original button text for loading state
   */
  $(document).on("DOMContentLoaded", function () {
    $(".wpnlweb-search-button").each(function () {
      $(this).data("original-text", $(this).text());
    });
  });

  /**
   * Handle dynamic shortcode loading (for AJAX-loaded content)
   */
  window.wpnlweb_init_shortcode = function (containerId) {
    const container = $("#" + containerId);
    if (container.length > 0) {
      initializeWPNLWebShortcodes();
    }
  };
})(jQuery);
