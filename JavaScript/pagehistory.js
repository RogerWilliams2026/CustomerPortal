"use strict";
/*
Created 27/09/2026 By Roger Williams

Javascript primarily for tracking pages opened by user 
as well as storing other values

*/

//for page history
export let dictPages = {};

//global var for current page
export let pgeCurrent = null;

//used when page loads to set header document
let docHeader = null;


export function SetHeader(objValue)
{
    let DIVMain = objValue.getElementById("divMain");

    //get child divMainHeader
    DIVMain = objValue.getElementById("divMainHeader");
    //store in open pages
    dictPages["name"] = DIVMain.innerText;
    dictPages["url"] = objValue.URL;
    
console.log(dictPages);
    
}

export function GetHeader() {
    return docHeader;
}

