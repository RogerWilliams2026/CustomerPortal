/*
Created 25/09/2026 By Roger Williams

Javascript for login screen

has code copied into the general.js script file for use in all forms
This code is the beforeunload handler to erase current logins from the system when
the user closes the application

stores logged in user in sessionStorage

*/


// function funcClearLoginData()
// {
//     const data = {
//         TXTUser: strUser
//     };

//     navigator.sendBeacon("/api/deletelogin.php",JSON.stringify(data));
// }

function funcClose(event)
{
  // Recommended
  event.preventDefault();

  //run php clear logins script
  //funcClearLoginData(); 

  //return NOTHING to avoid confirmation dialog
}


function escapeHtml(value) {

    const div = document.createElement("div");

    div.textContent = value ?? "";

    return div.innerHTML;
}

//main code
window.addEventListener("beforeunload",funcClose);

document
    .getElementById("frmLogin")
    .addEventListener("submit", async function(event) {

        event.preventDefault();

        const dataUser = {
            TXTUser: document.getElementById("TXTUser").value,
            TXTPassword: document.getElementById("TXTPassword").value
        };

       
        const response = await fetch("/api/checklogin.php", {

            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify(dataUser)
        });

        const result = await response.json();

        if (result.status) {
            //add user and name to session data
            sessionStorage.setItem("customerid",result.customerid)
            //add customer name to session data
            sessionStorage.setItem("customername",result.customername)

            if (result.status) {
                document.getElementById("frmLogin").reset();
                //go to main menu page
                window.location.href = "/html/main.html";
            }
            else    
            {

                alert(result.error);
            }
        }
        else    
        {

            alert("Login Error:\n" + result.error);
        }
    });



