
function openArticle() {
    console.log("yay! You submitted the form! Good cat!");
}

document.addEventListener("submit", function(event)
{
    event.preventDefault();
    openArticle()
}, false); 