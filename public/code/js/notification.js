function showNotification(icon, message) {
  var container = document.getElementById("notification-container");

  if (!container) {
    container = document.createElement("div");
    container.id = "notification-container";
    container.style.cssText =
      "position:fixed;top:1rem;right:1rem;z-index:2050;max-width:340px;width:100%;";
    document.body.appendChild(container);
  }

  var type =
    icon === "success" ? "success" : icon === "warning" ? "warning" : "danger";

  var alert = document.createElement("div");
  alert.className = "alert alert-" + type + " alert-dismissible shadow";
  alert.setAttribute("role", "alert");

  var close = document.createElement("button");
  close.type = "button";
  close.className = "close";
  close.setAttribute("data-dismiss", "alert");
  close.innerHTML = "&times;";

  alert.append(document.createTextNode(message), close);
  container.appendChild(alert);

  window.setTimeout(function () {
    if (alert.parentNode) {
      alert.parentNode.removeChild(alert);
    }
  }, 5000);
}

function escapeHtml(text) {
  var div = document.createElement("div");
  div.appendChild(document.createTextNode(text === null || text === undefined ? "" : String(text)));
  return div.innerHTML;
}

/**
 * Muestra un modal de error con la lista completa de campos inválidos.
 *
 * @param {string} message  Primer error / mensaje principal.
 * @param {object} errors   Objeto campo => [mensajes] (respuesta 422 del servidor).
 * @param {string} title    Título del modal (opcional).
 *
 * Uso manual: showErrorModal(res.message, res.errors)
 * Uso automático: cualquier AJAX que reciba un JSON con 'errors' (HTTP 422)
 * abre el modal sin escribir nada en la vista.
 */
function showErrorModal(message, errors, title) {
  if (typeof window.jQuery === "undefined") {
    window.alert(message || "Datos inválidos.");
    return;
  }

  var $ = window.jQuery;
  title = title || "Revisa el formulario";

  var listHtml = "";
  var errorCount = 0;

  if (errors && typeof errors === "object") {
    Object.keys(errors).forEach(function (field) {
      var messages = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
      messages.forEach(function (text) {
        errorCount++;
        listHtml +=
          '<li class="mb-1"><strong class="text-danger">' +
          escapeHtml(field) +
          "</strong>: " +
          escapeHtml(text) +
          "</li>";
      });
    });
  }

  var html =
    '<div class="modal fade" id="cms-error-modal" tabindex="-1" role="dialog" aria-labelledby="cms-error-modal-title" aria-hidden="true">' +
    '<div class="modal-dialog modal-dialog-centered" role="document">' +
    '<div class="modal-content border-danger">' +
    '<div class="modal-header bg-danger text-white">' +
    '<h5 class="modal-title" id="cms-error-modal-title">' +
    '<i class="fas fa-exclamation-triangle mr-2"></i>' +
    escapeHtml(title) +
    "</h5>" +
    '<button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>' +
    "</div>" +
    '<div class="modal-body">' +
    '<p class="mb-2">' +
    escapeHtml(message || "Algunos datos no son válidos.") +
    "</p>" +
    (listHtml
      ? '<ul class="pl-3 mb-0 small">' + listHtml + "</ul>"
      : "") +
    "</div>" +
    '<div class="modal-footer justify-content-between">' +
    '<span class="text-muted small">' +
    (errorCount > 0 ? errorCount + " campo(s) por corregir" : "Corrige los datos e inténtalo de nuevo") +
    "</span>" +
    '<button type="button" class="btn btn-danger" data-dismiss="modal">' +
    '<i class="fas fa-check mr-1"></i>Entendido</button>' +
    "</div>" +
    "</div>" +
    "</div>" +
    "</div>";

  $("#cms-error-modal").remove();
  $("body").append(html);
  $("#cms-error-modal").modal("show");
}

// Cualquier respuesta 422 con 'errors' abre el modal automáticamente.
if (typeof window.jQuery !== "undefined") {
  window.jQuery(document).ajaxError(function (event, xhr) {
    var data = xhr.responseJSON;

    if (data && data.errors) {
      showErrorModal(data.message, data.errors);
    }
  });
}