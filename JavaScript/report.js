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
//select lists
const CMBCreditMemos = document.getElementById("CMBCreditMemos");
//checkboxes
const CHKAllCreditMemos = document.getElementById("CHKAllCreditMemos");

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


async function funcShowReportCreditMemos(event)
{
    let dataUser = null;
    event.preventDefault();

   //show all credit memos?
   if (CHKAllCreditMemos.checked)
   {
         dataUser = {
            customerid: sessionStorage.getItem("customerid"),
            reporttype: "creditmemos",
            creditmemo: "all"
        };

        if (!CHKAllDates.checked)
        {
            //check if date selected
            if (document.getElementById("TXTDateFrom").value)
            {     
                let dteDateFrom = new Date(document.getElementById("TXTDateFrom").value);
                let dteDateTo = new Date(document.getElementById("TXTDateTo").value);
                //convert to japanese format
                dteDateFrom = dteDateFrom.toISOString();
                dteDateTo = dteDateTo.toISOString();

                //remove: T00:00:00.000Z
                dteDateFrom = dteDateFrom.replace("T00:00:00.000Z","");
                dteDateTo = dteDateTo.replace("T00:00:00.000Z","");
                
                dataUser = {
                    customerid: sessionStorage.getItem("customerid"),
                    reporttype: "creditmemos",
                    datefrom: dteDateFrom,
                    dateto: dteDateTo,
                    creditmemo: "all"
                };
            }
        }
   }
    else //user selected one credit memo
    {
        if (CMBCreditMemos.value !== "All")
        {   
        dataUser = {
            customerid: sessionStorage.getItem("customerid"),
            reporttype: "creditmemos",
            creditmemo: CMBCreditMemos.value
        };         
    }       
   }
   
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


async function funcGetCreditMemos(event)
{
    /*

      Created 09/11/2026 By Roger Williams

      gets list of credit memos from PHP puts into select list


    */

 //  event.preventDefault();
   
        const dataUser = {
            customerid: sessionStorage.getItem("customerid"),
            reporttype: "creditmemoslist",
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
                   //update select list with items
                   CMBCreditMemos.innerHTML = result.HTML;
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
  
if (document.body.contains(BTNShowReportCreditMemos))
{
   BTNShowReportCreditMemos.addEventListener("click", funcShowReportCreditMemos);
   //for this page also need to get the credit memo invoice numbers into a select list!
   funcGetCreditMemos();
};