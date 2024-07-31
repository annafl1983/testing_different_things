
var content1 = `<iframe width="560" height="315" src="https://www.youtube.com/embed/DHfRfU3XUEo?si=prirIj_dJ1a1Wnw9" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>`;

var content2 = `<iframe width="560" height="200" src="https://www.youtube.com/embed/DHfRfU3XUEo?si=prirIj_dJ1a1Wnw9" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>`;

var iframe1 = document.querySelector(".iframe1");
iframe1.innerHTML = content1;

var iframe2 = document.querySelector(".iframe2");
iframe2.innerHTML = content2;  

var toggle = (element) => {
if (element.classList.contains("invisible"))
{element.classList.remove("invisible")}
else {element.classList.add("invisible")}
}

var button1 = document.querySelector(".button1");
var button2 = document.querySelector(".button2");

button1.addEventListener("click", toggle(iframe1), false);

button2.addEventListener("click", toggle(iframe2), false);