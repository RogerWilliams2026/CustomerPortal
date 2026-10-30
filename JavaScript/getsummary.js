


export async function funcGetSummary()
{
//  let DIVSummary = document.getElementById("divSummaryHeader").children[0]; 
  let DIVSummary = document.getElementById("divSummary"); 

       const dataUser = {
            customerID: sessionStorage.getItem("customerid"),
          };

       const response = await fetch("/api/getcustomersummary.php", {
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
               DIVSummary.innerHTML = result.SummaryHTML;
            }
            else    
            {
                alert("Error:\n" + result.error);
            }
        }
        else    
        {
            alert("JSON Error:\n" + result.error);
        }



//chatGPT debug code
// const dataUser = {
//     customerID: sessionStorage.getItem("customerid")
// };

// const response = await fetch("/api/getcustomersummary.php", {
//     method: "POST",
//     headers: {
//         "Content-Type": "application/json"
//     },
//     body: JSON.stringify(dataUser)
// });

// const rawResponse = await response.text();

// console.log("HTTP status:", response.status);
// console.log("Raw response:", JSON.stringify(rawResponse));

// let result;

// try {
//     result = JSON.parse(rawResponse);
//     console.log("Parsed JSON:", result);
// } catch (error) {
//     console.error("JSON parsing failed:", error);
//     alert("Invalid JSON. Check the console for the raw response.");
//     throw error;
// }

// console.log("Status:", result.status);

// if (result.status) {
//     DIVSummary.innerHTML = result.SummaryHTML;
// } else {
//     alert("Error: " + (result.error || "Unknown error"));
// }

}
