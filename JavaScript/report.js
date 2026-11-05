/*
  Create 05/11/2026 By Roger Williams

  used by report forms to populate their iframes with report results
  luckily ALL reports only use date from/to as options!


*/


//link to forms print report button
const BTNShowReportAR = document.getElementById("BTNShowReportAR");
const BTNShowReportCreditMemos= document.getElementById("BTNShowReportCreditMemos");
const BTNShowReportInvoices = document.getElementById("BTNShowReportInvoices");
//get report frame to show results in
let FARReport = document.getElementById("fraReport");

async function funcShowReportAR(event)
{
   let dteDateFrom = new Date(document.getElementById("TXTDateFrom").value);
   let dteDateTo = new Date(document.getElementById("TXTDateTo").value);
   //convert to japanese format
   dteDateFrom = dteDateFrom.toISOString();
   alert(dteDateFrom);
   dteDateTo = dteDateTo.toISOString();
   alert(dteDateTo);


    event.preventDefault();
   
        const dataUser = {
            customerid: sessionStorage.GetItem("customerid"),
            reporttype: "AR",
            datefrom: dteDateFrom,
            dateto: dteDateTo
        };

       //send to PHP
        const response = await fetch("/api/getreportdata.php", {

            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify(dataUser)
        });

        const result = await response.json();

        if (result.status) {
            //get HTML from promise and put into DIV

            if (result.status) {
               FARReport.innerHTML = result.HTML;
            }
            else    
            {
                alert("Error:\n" + result.error);
            }
        }
        else    
        {
            alert("Error Getting Report Data!:\n" + result.error);
        };
};


//create handler for show reports submit button
document.getElementById("BTNShowReportAR").addEventListener("submit", funcShowReportAR);
