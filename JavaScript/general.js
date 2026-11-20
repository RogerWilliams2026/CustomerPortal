/*
Created 25/09/2026 By Roger Williams

Javascript for non login screens

This code is the beforeunload handler to erase current logins from the system when
the user closes the application

logged in user in sessionStorage

*/

import * as modPageHistory from "../JavaScript/pagehistory.js";

let docHeader = null;
let BTNLogout = null;
let CMBPages = document.getElementById("CMBPages");

function funcClearLoginData()
{
    const data = {
        TXTUser: sessionStorage.getItem("user")
    };

    navigator.sendBeacon("/api/deletelogin.php",JSON.stringify(data));

}

function funcClose(event)
{
  //remove page from open pages dictionary
  delete modPageHistory.dictPages[document.URL];
  //return NOTHING to avoid confirmation dialog
  event.return  = '';
}


function escapeHtml(value) {

    const div = document.createElement("div");

    div.textContent = value ?? "";

    return;
}


function funcLogOutClicked()
{
   //clear login details
   funcClearLoginData();

   window.close(parent);
   window.top.location.href = "../index.html";

}

function funcPageSelected(event)
{
   window.location.replace(CMBPages.value); 

}

export function SetLogoutButtonHandler()
{
   //get header
   let FRAHeader = window.parent.document.getElementsByClassName("clsHeader");

   // Make sure an iframe exists
    if (FRAHeader.length === 0)
    {
        console.log("Header iframe not found");
        return;
    }
   let FRAFrame = FRAHeader[0];
   //make sure get one that exists!
   let docTemp = FRAFrame.contentDocument || FRAFrame.contentWindow.document;
   let BTNLogout = docTemp.getElementById("btnLogout")
   BTNLogout.addEventListener("click",funcLogOutClicked);
}

export function funcOpenMenuItem(strWhat) 
{
   let pgeTemp = null;

   switch (strWhat)
   {
     case 1:
        pgeTemp = window.open("customer_ar.html", "_self");
        modPageHistory.dictPages[pgeTemp.title] = pgeTemp.URL; 
        modPageHistory.pgeCurrent = pgeTemp.URL; 
     case 2:
        pgeTemp = window.open("creditmemos.html", "_self");
        modPageHistory.dictPages[pgeTemp.title] = pgeTemp.URL; 
        modPageHistory.pgeCurrent = pgeTemp.URL; 
     case 3:
        pgeTemp = window.open("invoicereprint.html", "_self");
        modPageHistory.dictPages[pgeTemp.title] = pgeTemp.URL; 
        modPageHistory.pgeCurrent = pgeTemp.URL;                 
   }
}


//***** main code***** */

//put function into global window object so menu option hyperlink can
//run code in this module on click
window.funcOpenMenuItem = funcOpenMenuItem;

//main code
window.removeEventListener('beforeunload', funcClose);
window.addEventListener("beforeunload", funcClose);

//store current page in open pages dictionary
modPageHistory.dictPages[document.title] = document.URL;

if (document.body.contains(CMBPages))
{
   //add event listeners 
   CMBPages.addEventListener("change",funcPageSelected);
};


