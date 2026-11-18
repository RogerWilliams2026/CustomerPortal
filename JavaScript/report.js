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
const CMBInvoices = document.getElementById("CMBInvoices");
//checkboxes
const CHKAllCreditMemos = document.getElementById("CHKAllCreditMemos");
const CHKAllOpenCreditMemos = document.getElementById("CHKAllOpenCreditMemos");
const CHKAllDates = document.getElementById("CHKAllDates");
const CHKAllInvoices = document.getElementById("CHKAllInvoices");
//date controls
const TXTDateFrom = document.getElementById("TXTDateFrom");
const TXTDateTo = document.getElementById("TXTDateTo");


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
            creditmemo: "all",
            creditmemoopen: "false"
        };
console.log(dataUser);
        //special handler for if user wants to see ONLY open credit memos
        if (CHKAllOpenCreditMemos.checked)
        {   
            dataUser = {
                customerid: sessionStorage.getItem("customerid"),
                reporttype: "creditmemos",
                creditmemo: "all",
                creditmemoopen: "true"
            };         
        }      
console.log(dataUser);
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
                    creditmemo: "all",
                    creditmemoopen: "false"
                };

                //special handler for if user wants to see ONLY open credit memos
                if (CHKAllOpenCreditMemos.checked)
                {   
                    dataUser = {
                        customerid: sessionStorage.getItem("customerid"),
                        reporttype: "creditmemos",
                        datefrom: dteDateFrom,
                        dateto: dteDateTo,
                        creditmemo: "all",
                        creditmemoopen: "true"
                };         
                }   
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
                creditmemo: CMBCreditMemos.value,
                creditmemoopen: "false"
            };      
        
        //special handler for if user wants to see ONLY open credit memos
        if (CHKAllOpenCreditMemos.checked)
        {   
            dataUser = {
                customerid: sessionStorage.getItem("customerid"),
                reporttype: "creditmemos",
                creditmemo: CMBCreditMemos.value,
                creditmemoopen: "true"
            };         
        }           
    }       
   }
alert(CHKAllOpenCreditMemos.checked);   
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
      Modified 17/11/2026 By Roger Williams

      added code to process CHKAllOpenCreditMemos

      Created 09/11/2026 By Roger Williams

      gets list of credit memos from PHP puts into select list


    */

    if (CHKAllOpenCreditMemos.checked)
    { 
        const dataUser = {
            customerid: sessionStorage.getItem("customerid"),
            reporttype: "creditmemoslist",
            creditmemoopen: "true"
        };
    }
    else
    { 
        const dataUser = {
            customerid: sessionStorage.getItem("customerid"),
            reporttype: "creditmemoslist",
            creditmemoopen: "false"
        };
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



async function funcGetInvoices(event)
{
    /*

      Created 12/11/2026 By Roger Williams

      gets list of invoices from PHP puts into select list


    */

   
        const dataUser = {
            customerid: sessionStorage.getItem("customerid"),
            reporttype: "invoiceslist",
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
                   CMBInvoices.innerHTML = result.HTML;
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


async function funcShowReportInvoices(event)
{
    let dataUser = null;
    event.preventDefault();

    //show all invoices?
   if (CHKAllInvoices.checked)
   {
         dataUser = {
            customerid: sessionStorage.getItem("customerid"),
            reporttype: "invoices",
            invoice: "all"
        };
console.log(dataUser);

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
                    reporttype: "invoices",
                    datefrom: dteDateFrom,
                    dateto: dteDateTo,
                    invoice: "all"
                };
console.log(dataUser);                
            }
        }
   }
    else //user selected one invoice
    {
        if (CMBInvoices.value !== "All")
        {   
            dataUser = {
                customerid: sessionStorage.getItem("customerid"),
                reporttype: "invoices",
                invoice: CMBInvoices.value
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
console.log(result.HTML);                    
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


function funcAllDatesChecked(event)
{
    event.preventDefault();

    
}

function funcAllInvoicesChecked(event)
{
    event.preventDefault();

    
}

function funcInvoiceSelected(event)
{
    event.preventDefault();

    CHKAllInvoices.checked = false;
}

function funcCreditMemoSelected(event)
{
    event.preventDefault();

    CHKAllCreditMemos.checked = false;
}

function funcFilterCreditMemos(event)
{
    event.preventDefault();
  
    funcGetCreditMemos();
}

function funcDateSelected(event)
{
    event.preventDefault();

    CHKAllDates.checked = false;
    
}
//******end funcs*****


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
   //add event listeners  
   BTNShowReportCreditMemos.addEventListener("click", funcShowReportCreditMemos);
   TXTDateFrom.addEventListener("change",funcDateSelected);
   TXTDateTo.addEventListener("change",funcDateSelected);   
   CMBCreditMemos.addEventListener("change", funcCreditMemoSelected);
   CHKAllOpenCreditMemos.addEventListener("change", funcFilterCreditMemos)
   //for this page also need to get the credit memo invoice numbers into a select list!
   funcGetCreditMemos();
};

if (document.body.contains(BTNShowReportInvoices))
{
   //add event listeners 
   BTNShowReportInvoices.addEventListener("click", funcShowReportInvoices);
   CHKAllDates.addEventListener("change",funcAllDatesChecked);
   CHKAllInvoices.addEventListener("change",funcAllInvoicesChecked);
   CMBInvoices.addEventListener("change",funcInvoiceSelected);
   TXTDateFrom.addEventListener("change",funcDateSelected);
   TXTDateTo.addEventListener("change",funcDateSelected);
   //for this page also need to get the invoice numbers into a select list!
   funcGetInvoices();
};