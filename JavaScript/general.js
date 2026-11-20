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
   alert("logout");
   //clear login details
   funcClearLoginData();

   
alert(parent.document.title);

   window.close(modPageHistory.pgeCurrent);
 //  window.location.replace(parent.document.URL);
 //  window.location.replace("../index.html");

}

function funcPageSelected(event)
{
   window.location.replace(CMBPages.value); 

}

export function SetLogoutButtonHandler()
{
  //get header DOM  
  docHeader = modPageHistory.GetHeader();
  BTNLogout = docHeader.getElementById("btnLogout")
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


