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

function funcClearLoginData()
{
    const data = {
        TXTUser: sessionStorage.getItem("user")
    };

    navigator.sendBeacon("/api/deletelogin.php",JSON.stringify(data));
    //remove page from open pages dictionary
    delete modPageHistory.dictPages[document.URL];
}

function funcClose(event)
{
  // Recommended
  event.preventDefault();

  //run php clear logins script
  funcClearLoginData(); 

  //return NOTHING to avoid confirmation dialog
}


function escapeHtml(value) {

    const div = document.createElement("div");

    div.textContent = value ?? "";

    return div.innerHTML;
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
   }
}


//put function into global window object so menu option hyperlink can
//run code in this module on click
window.funcOpenMenuItem = funcOpenMenuItem;

//main code
window.addEventListener("beforeunload",funcClose);

//store current page in open pages dictionary
modPageHistory.dictPages[document.title] = document.URL;





