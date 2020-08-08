{literal}
  <style>
    .cleanslate {
      vertical-align: baseline;
      font-weight: inherit;
      font-family: inherit;
      font-style: inherit;
      font-size: 100%;
      border: 0 none;
      outline: 0;
      padding: 0;
      margin: 0;
    }

    .cleanslate img {
      margin-bottom:10px;
    }

    body {
      background-color: white;
    }

    #civicrm-footer, #access, #printer-friendly, h1.title {
      display: none;
    }

    .pageTitle {
      background-color: white;
    }

    body#bootstrap-theme, #bootstrap-theme {
      background-color: white !important;
    }
  </style>
{/literal}
<div id="bootstrap-theme">
  <h1 class="pageTitle" style="background-color:white;margin-bottom:3px;">Mailings Live Preview <i class="crm-i fa-refresh" id="refresh" style="font-size:24px;cursor:pointer;"></i>
    <label style="font-size: 12px;padding-left: 2rem;"><input type="checkbox" id="autorefresh" style="margin-top: -2px;" checked> Auto-refresh every 5s</label>
  </h1>

  {capture assign=mailingURL}{crmURL p='civicrm/mailing/send' q="mid=`$mid`&continue=true&reset=1"}{/capture}

  <h4 style="background-color:white;margin-top:0;padding-top:0;padding-left:1px;">Mailing: <a href="{$mailingURL}" target="_blank">{$mailingTitle}</a></h4>
  <div class="col-xs-4 col-md-4 col-lg-2" id="recipients">
    <button type="button" class="btn btn-primary" id="randomise" style="width:100%;">Randomise</button>
    <input type="text" class="form-control" id="searchRec" style="width:100%;" placeholder="Search">
    <div class="list-group" id="recipientList" style="height:75vh;overflow:auto;border-bottom:1px solid #ddd;">
      <p class="text-center" style="padding-top:1rem;"><i class="crm-i fa-spinner fa-spin" style="font-size:48px;"></i></p>
    </div>
  </div>
  <div class="col-xs-8 col-md-8 col-lg-10 cleanslate" id="mailPreview"><small id="warning" style="display:none;">This tool is so you can check the <em>content</em> of your email. It won't necessarily <em>look</em>
      exactly like the above in people's email clients, as all email clients have their quirks.</small></div>


</div>

{literal}
  <script>
    (function ($) {
      var mailingID = CRM.vars.messagetemplatetools.mid;
      var autoRefresh = true;
      var working = false;

      CRM.api3('Messagetemplatetools', 'getrecipients', {
        "mailing_id": mailingID
      }).done(function (result) {
        var recipients = [];
        var count = result.count;
        if (count == 0) {
          $('#recipientList').html('No recipients found');
          return;
        } else {
          $.each(result.values, function (i, v) {
            str = '<a href="#" class="list-group-item recipient" cid=' + i + '>';
            if (i == v) {
              str += i + '</a>'
            } else {
              str += i + ' - ' + v + '</a>'
            }
            recipients.push(str);
          });

          $('#randomise').text('Randomise ' + count + ' contacts');
          if (count == 5000) {
            $('#randomise').append('<div style="font-size:0.7em;line-height:1em;"><br />(Mailing was large: a random sample of <br/>5000 was selected)</div>');
          }
          $('#recipientList').html(recipients);
          $('#recipientList a').first().trigger('click');
        }
      });


      function addSpinner() {
        $('#warning').hide();
        $('#mailPreview').block({
          message: '<div class="fa-3x"><i class="fa fa-spinner fa-spin"></i></div>',
          css: {
            backgroundColor: 'transparent',
            border: 'none'
          }
        });
      }

      function refreshPreview(id = null, background) {
        working = true;
        if (!background) {
          addSpinner();
        }
        if (id == null) {
          id = $('#recipientList a.active').attr('cid');
        }
        CRM.api3('Messagetemplatetools', 'getmailing', {
          "mailing_id": mailingID,
          "contact_id": id
        }).done(function (result) {
          checkRefreshPreview(result.values);
        }).fail(function(jqHXR, status) {
          $('#mailPreview').html('<p class="text-center" style="padding-top: 10rem;">Error retrieving content</p>');
          working = false;
        });
      }

      function checkRefreshPreview(html) {
        var existinghtml = $('#mailPreview').html();
        if (html != existinghtml && working) {
          $('#mailPreview').html(html);
          $('#warning').show();
        }
        working = false;
      }

      function randomise() {
        var parent = $("#recipientList");
        var divs = parent.children();
        while (divs.length) {
          parent.append(divs.splice(Math.floor(Math.random() * divs.length), 1)[0]);
        }
      }

      $('body').on('click', 'a.recipient', function (e) {
        e.preventDefault();
        $('.recipient.active').removeClass('active');
        $(this).addClass('active');

        var id = $(this).attr('cid');
        refreshPreview(id, false);
      });

      $('body').on('click', '#randomise', function (e) {
        randomise();
      });

      $("#searchRec").on("keyup", function () {
        var value = $(this).val().toLowerCase();
        $("#recipientList a").filter(function () {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
      });

      $("#refresh").on("click", function () {
        refreshPreview(null, false);
      });

      $("#autorefresh").on("change", function () {
        if (this.checked) {
          autoRefresh = true;
        } else {
          autoRefresh = false;
        }
      });

      setInterval(function() {
        if (autoRefresh && !working) {
          refreshPreview(null, true);
        }
      }, 5000);

      //if inactive, stop autorefreshing
      var inactivityTime = function () {
        var t;
        window.onload = resetTimer;
        // DOM Events
        document.onmousemove = resetTimer;
        document.onkeypress = resetTimer;

        function logout() {
          autoRefresh = false;
          $('#autoRefresh').prop('checked', 'false')
          //location.href = 'logout.php'
        }

        function resetTimer() {
          clearTimeout(t);
          t = setTimeout(logout, 600000)
          // 1000 milisec = 1 sec
        }
      };

    })(CRM.$);

  </script>
{/literal}
