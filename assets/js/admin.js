jQuery(document).ready(function ($) {
  console.log(cf7SaveToDb);
  var currentSubmissionId = null;

  // View Details button click handler
  $(".view-details").on("click", function () {
    var submissionId = $(this).data("id");
    currentSubmissionId = submissionId; // Store current submission ID
    var modal = $("#submission-modal");

    // AJAX request to get submission details
    $.ajax({
      url: cf7SaveToDb.ajaxUrl,
      type: "POST",
      data: {
        action: "get_submission_details",
        _ajax_nonce: cf7SaveToDb.nonce,
        id: submissionId,
      },
      success: function (response) {
        if (response.success) {
          var html = "<h2>Submission Details</h2>";
          $.each(response.data, function (key, value) {
            html += '<div class="submission-field">';
            html += "<label>" + key + "</label>";
            html += "<div>" + value + "</div>";
            html += "</div>";
          });
          $("#submission-details").html(html);
          modal.show();
        } else {
          alert("Error loading submission details");
        }
      },
      error: function (xhr, status, error) {
        console.log("AJAX Error:", xhr.status, xhr.responseText);
        alert("Error: " + xhr.responseText);
      },
    });
  });

  // Edit submission handler
  $(".edit-submission").on("click", function () {
    if (!currentSubmissionId) return;

    // Show premium notice
    var modal = $("#submission-modal");
    var detailsContainer = $("#submission-details");

    // Hide regular action buttons
    $(".modal-actions").hide();

    // Add premium notice
    detailsContainer.append(
      '<div class="premium-notice">' +
        "<h3>Premium Feature</h3>" +
        "<p>The edit feature is only available in the premium version.</p>" +
        '<a href="https://ruhulamin.me/cf7-to-db-pro" target="_blank" class="button button-primary">Upgrade to Pro</a> ' +
        '<button class="button cancel-premium-notice">Cancel</button>' +
        "</div>"
    );
  });

  // Cancel premium notice handler
  $(document).on("click", ".cancel-premium-notice", function () {
    // Remove premium notice and show action buttons
    $(".premium-notice").remove();
    $(".modal-actions").show();
  });

  // Save changes handler
  $(document).on("click", ".save-changes", function () {
    var formData = {};
    $("#submission-details .submission-field").each(function () {
      var input = $(this).find("input");
      if (input.length) {
        formData[input.attr("name")] = input.val();
      }
    });

    $.ajax({
      url: cf7SaveToDb.ajaxUrl,
      type: "POST",
      data: {
        action: "update_submission",
        _ajax_nonce: cf7SaveToDb.nonce,
        id: currentSubmissionId,
        data: formData,
      },
      success: function (response) {
        if (response.success) {
          location.reload(); // Refresh to show updated data
        } else {
          alert("Error updating submission");
        }
      },
      error: function (xhr) {
        alert("Error: " + xhr.responseText);
      },
    });
  });

  // Cancel edit handler
  $(document).on("click", ".cancel-edit", function () {
    // Reload submission details to reset the form
    $(".view-details[data-id='" + currentSubmissionId + "']").trigger("click");
  });

  // Delete submission handler
  $(".delete-submission").on("click", function () {
    if (!currentSubmissionId) return;

    if (!confirm("Are you sure you want to delete this submission?")) {
      return;
    }

    $.ajax({
      url: cf7SaveToDb.ajaxUrl,
      type: "POST",
      data: {
        action: "delete_submission",
        _ajax_nonce: cf7SaveToDb.nonce,
        id: currentSubmissionId,
      },
      success: function (response) {
        if (response.success) {
          location.reload(); // Refresh to remove deleted item
        } else {
          alert("Error deleting submission");
        }
      },
      error: function (xhr) {
        alert("Error: " + xhr.responseText);
      },
    });
  });

  // Close modal when clicking the X
  $(".cf7-modal-close").on("click", function () {
    $("#submission-modal").hide();
  });

  // Close modal when clicking outside
  $(window).on("click", function (event) {
    var modal = $("#submission-modal");
    if (event.target == modal[0]) {
      modal.hide();
    }
  });
});
