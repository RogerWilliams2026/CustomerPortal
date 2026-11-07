/*
  Create 05/11/2026 By Roger Williams

  used by report forms to populate their iframes with report results
  luckily ALL reports only use date from/to as options!


*/


//link to forms print report button
const BTNShowReportAR = document.getElementById("BTNShowReportAR");
const BTNShowReportBacklog = document.getElementById("BTNShowReportBacklog");
const BTNShowReportCreditMemos= document.getElementById("BTNShowReportCreditMemos");
const BTNShowReportInvoices = document.getElementById("BTNShowReportInvoices");
//get report frame to show results in
let DIVReport = document.getElementById("fraReport");


async function funcShowReportBacklog(event)
{

   event.preventDefault();
   
        const dataUser = {
            customerid: sessionStorage.getItem("customerid"),
            reporttype: "backlog",
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
                if (result.norecords == false)
                {
                   //update iframe with HTML   
                   DIVReport.src="javascript:'" + result.HTML + "'";
                }
                else
                {
                   alert("No Records Found!");  
                }    
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


async function funcShowReportAR(event)
{
if (!document.getElementById("TXTDateFrom").value)
{
    alert("Missing From Date");
    return;
}        

if (!document.getElementById("TXTDateTo").value)
{
    alert("Missing To Date");
    return;
}        

   let dteDateFrom = new Date(document.getElementById("TXTDateFrom").value);
   let dteDateTo = new Date(document.getElementById("TXTDateTo").value);
   //convert to japanese format
   dteDateFrom = dteDateFrom.toISOString();
   dteDateTo = dteDateTo.toISOString();

   //remove: T00:00:00.000Z
   dteDateFrom = dteDateFrom.replace("T00:00:00.000Z","");
   dteDateTo = dteDateTo.replace("T00:00:00.000Z","");

   event.preventDefault();
   
        const dataUser = {
            customerid: sessionStorage.getItem("customerid"),
            reporttype: "ar",
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
                if (result.norecords == false)
                {
                   //update iframe with HTML   
                   DIVReport.src="javascript:'" + result.HTML + "'";
                }
                else
                {
                   alert("No Records Found!");  
                }    
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


//create handler for show report button
if (document.body.contains(BTNShowReportAR))
{
   BTNShowReportAR.addEventListener("click", funcShowReportAR);
};

if (document.body.contains(BTNShowReportBacklog))
{
   BTNShowReportBacklog.addEventListener("click", funcShowReportBacklog);
};
  