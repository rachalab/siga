/** @format */

(function ($, Drupal, drupalSettings) {
  "use strict";

  function countTotal(status, operation = '+') {
    status = (status + '').replace(' ', '');
    let selectorTotal = $('.status-' + status + ' .total-status');
    let total = parseInt(selectorTotal.text());
    if (operation == '+') {
      total += 1;
    } else {
      total -= 1;
    }
    selectorTotal.text(total);
    return total;
  }

  function countPoint(status, point, operation = '+') {
    status = (status + '').replace(' ', '');
    point = parseInt(point);
    let selectorTotal = $('.status-' + status + ' .card-header .total .badge');
    let total = parseInt(selectorTotal.text());
    if (operation == '+') {
      total += point;
    } else {
      total -= point;
    }
    selectorTotal.text(total);
    return total;
  }

  Drupal.behaviors.Kanban = {
    attach: function attach(context) {
      $(once('Kanban', ".views-view-kaban", context))
        .each(function () {
          let kanbanHeight = $(".views-view-kaban").height();
          if(kanbanHeight < 180) {
            kanbanHeight = 180*2;
          }
          $(".panel-body [droppable=true]").css("min-height", kanbanHeight - 180 + "px");
          if(drupalSettings.views_kanban !== undefined && drupalSettings.views_kanban.permission_drag){
            draggableInit();
          }
          // Detect variable to open
          let params = new window.URLSearchParams(window.location.search);
          if(params.get('kanbanTicket')) {
            $('#viewkanban' + params.get('kanbanTicket')).click();
          }
        });

      function draggableInit() {
        let entityId, type, currentStatus, currentDrag;

        $(".views-view-kaban [draggable=true]").bind("dragstart", function (event) {
          entityId = $(this).data("id");
          type = $(this).data("type");
          currentStatus = $(this).data("value");
          currentDrag = $(this).attr('id');
          countTotal(currentStatus, '-');
          countPoint(currentStatus, $(this).data("point"), '-');
          event.originalEvent.dataTransfer.setData("text/plain", event.target.getAttribute("id"));
        });

        $(".views-view-kaban .panel-body").bind("dragover", function (event) {
          let color = $(this).data('color');
          $(this).addClass(`bg-${color}-subtle`);
          event.preventDefault();
        });
        $(".views-view-kaban .panel-body").bind("dragleave", function () {
          let color = $(this).data('color');
          $(this).removeClass(`bg-${color}-subtle`);
        });

        $(".views-view-kaban [droppable=true]").bind("drop", function (event) {
          let view_kanban = $(this).closest(".views-view-kaban");
          let view_id = view_kanban.data("view_id");
          let display_id = view_kanban.data("display_id");
          let stateValue = $(this).data("value");

          let spinners =
            '<div class="spinners d-flex justify-content-center" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="' + Drupal.t('Loading') + '…' + '">' +
            '<div class="spinner-border" role="status"><span class="sr-only"></span></div>' +
            '</div>';
          if (currentStatus != stateValue) {
            let elementId = event.originalEvent.dataTransfer.getData("text/plain");

            $(this).prepend(spinners);
            //before post
            if (type && entityId && stateValue) {
              $('#' + currentDrag).data('value', stateValue);
              // Generate URL for AJAX call.
              let url = "views-kanban/update-state/" + view_id + "/" + display_id + "/" + entityId + "/" + stateValue;
              let article = $("#" + elementId).detach();
              $(this).prepend(article);
              let color = $(this).parent().data('color');
              $(this).parent().removeClass(`bg-${color}-subtle`);
              let total = countTotal(stateValue, '+');
              let point = countPoint(stateValue, article.data('point'), '+');
              let that = $(this);
              $.ajax({
                url: Drupal.url(url),
                success: function (result) {
                  that.find(".spinners").remove();
                  // Emit event viewKanban then another javascript that can catch it.
                  const event = new CustomEvent('viewsKanban', {
                    detail: {
                      view_id: view_id,
                      display_id: display_id,
                      entityId: entityId,
                      state: currentStatus,
                      to: stateValue,
                      total: total,
                      point: point
                    }
                  });
                  document.dispatchEvent(event);
                },
                error: function (xhr, status, error) {
                  alert(
                    Drupal.t("An error occurred during the update of the entity. Please consult the watchdog.")
                  );
                },
              });
            }
          }

          event.preventDefault();
        });
      }
    },
  };

  Drupal.behaviors.kanbanColumnToggle = {
    attach: function (context, settings) {

      $(once('KanbanToggle', ".kanban-column-controls", context)).each(function () {
        let viewId = $(this).data('view_id');
        let displayId = $(this).data('display_id');
        // Restore state in localStorage.
        let kanbanToggle = JSON.parse(localStorage.getItem('kanbanToggle')) || {};
        kanbanToggle[viewId] = kanbanToggle[viewId] || {};
        kanbanToggle[viewId][displayId] = kanbanToggle[viewId][displayId] || {};
        // Get a list of checkboxes and set their status.
        const columnCheckboxes = $(this).find('input');
        if (Object.keys(kanbanToggle[viewId][displayId]).length === 0) {
          columnCheckboxes.each(function () {
            let columnStatus = $(this).val().replace(/\s/g, '');
            kanbanToggle[viewId][displayId][columnStatus] = $(this).is(":checked");
            localStorage.setItem('kanbanToggle', JSON.stringify(kanbanToggle));
          });
        }
        columnCheckboxes.each(function () {
          let columnStatus = $(this).val().replace(/\s/g, '');
          let isVisible = kanbanToggle[viewId][displayId][columnStatus] || false;
          $(this).prop('checked', isVisible);
          $('.status-' + columnStatus).toggle(isVisible);
        });
        // Handle events when checkboxes change
        columnCheckboxes.on('change', function () {
          let columnStatus = $(this).val().replace(/\s/g, '');
          let isVisible = $(this).prop('checked');
          kanbanToggle[viewId][displayId][columnStatus] = isVisible;
          $('.status-' + columnStatus).toggle(isVisible);
          localStorage.setItem('kanbanToggle', JSON.stringify(kanbanToggle));
        });

      });

    }
  };

})(jQuery, Drupal, drupalSettings);
