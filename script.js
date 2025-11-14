document.addEventListener("DOMContentLoaded", () => {
  const apiUrl = "api/recados.php";

  const form = document.getElementById("recado-form");
  const formTitle = document.getElementById("form-title");
  const recadoIdInput = document.getElementById("recado-id");
  const recadoMensagemInput = document.getElementById("recado-mensagem");
  const btnSalvar = document.getElementById("btn-salvar");
  const btnCancelar = document.getElementById("btn-cancelar");

  const recadosLista = document.getElementById("recados-lista");
  const loadingIndicator = document.getElementById("loading-indicator");

  let isEditing = false;
  let currentEditId = null;

  const setAppLoading = (isLoading) => {
    if (isLoading) {
      loadingIndicator.classList.remove("hidden");
    } else {
      loadingIndicator.classList.add("hidden");
    }

    const allButtons = document.querySelectorAll("button, .btn-favorito");
    allButtons.forEach((button) => {
      button.disabled = isLoading;
    });
  };

  const fetchRecados = async () => {
    setAppLoading(true);
    try {
      const response = await fetch(apiUrl);
      if (!response.ok) {
        throw new Error(`Erro HTTP: ${response.status}`);
      }
      const recados = await response.json();
      renderRecados(recados);
    } catch (error) {
      console.error("Falha ao buscar recados:", error);
      showError("Não foi possível carregar os recados.");
    } finally {
      setAppLoading(false);
    }
  };

  const renderRecados = (recados) => {
    recadosLista.innerHTML = "";

    if (recados.length === 0) {
      recadosLista.innerHTML =
        "<p>Nenhum recado encontrado. Seja o primeiro a adicionar!</p>";
      return;
    }

    recados.forEach((recado) => {
      const card = document.createElement("div");
      card.className = "recado-card";
      card.dataset.id = recado.id;

      const isFavorito = recado.status == 1;
      const dataFormatada = new Date(recado.data_criacao).toLocaleString(
        "pt-BR"
      );

      card.innerHTML = `
                <div class="recado-header">
                    <span class="recado-data">${dataFormatada}</span>
                    <button class="btn-favorito ${
                      isFavorito ? "favorito" : ""
                    }" 
                            title="${
                              isFavorito
                                ? "Remover dos favoritos"
                                : "Adicionar aos favoritos"
                            }">
                        &#9733; </button>
                </div>
                <div class="recado-body">
                    <p class="recado-mensagem-placeholder"></p>
                </div>
                <div class="recado-actions">
                    <button class="btn btn-warning btn-editar">Editar</button>
                    <button class="btn btn-danger btn-excluir">Excluir</button>
                </div>
            `;

      card.querySelector(".recado-mensagem-placeholder").textContent =
        recado.mensagem;

      recadosLista.appendChild(card);
    });
  };

  const resetForm = () => {
    form.reset();
    recadoIdInput.value = "";
    isEditing = false;
    currentEditId = null;
    formTitle.textContent = "Adicionar Novo Recado";
    btnSalvar.textContent = "Salvar";
    btnCancelar.classList.add("hidden");
  };

  const setupEditForm = async (id) => {
    setAppLoading(true);
    try {
      const response = await fetch(`${apiUrl}?id=${id}`);
      if (!response.ok) {
        throw new Error("Recado não encontrado.");
      }
      const recado = await response.json();

      recadoIdInput.value = recado.id;
      recadoMensagemInput.value = recado.mensagem;

      isEditing = true;
      currentEditId = recado.id;
      formTitle.textContent = "Editando Recado";
      btnSalvar.textContent = "Atualizar";
      btnCancelar.classList.remove("hidden");

      form.scrollIntoView({ behavior: "smooth" });
    } catch (error) {
      console.error("Falha ao carregar recado para edição:", error);
      showError(error.message);
    } finally {
      setAppLoading(false);
    }
  };

  const showError = (message) => {
    alert(`Ocorreu um Erro: ${message}`);
  };

  const handleFormSubmit = async (e) => {
    e.preventDefault();

    const mensagem = recadoMensagemInput.value.trim();
    const id = recadoIdInput.value;

    if (!mensagem) {
      showError("A mensagem não pode estar vazia.");
      return;
    }

    setAppLoading(true);
    try {
      let response;
      if (isEditing) {
        response = await fetch(apiUrl, {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: id, mensagem: mensagem }),
        });
      } else {
        response = await fetch(apiUrl, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ mensagem: mensagem }),
        });
      }

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.mensagem || "Falha na operação.");
      }

      resetForm();
      await fetchRecados();
    } catch (error) {
      console.error("Erro ao salvar recado:", error);
      showError(error.message);
    } finally {
      setAppLoading(false);
    }
  };

  const handleRecadosListClick = async (e) => {
    const target = e.target;
    const card = target.closest(".recado-card");

    if (!card) return;

    const id = card.dataset.id;

    try {
      if (target.classList.contains("btn-excluir")) {
        if (!confirm("Tem certeza que deseja apagar este post?")) {
          return;
        }

        setAppLoading(true);
        const response = await fetch(apiUrl, {
          method: "DELETE",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: id }),
        });

        if (!response.ok) throw new Error("Falha ao excluir.");

        await fetchRecados();
      }

      if (target.classList.contains("btn-editar")) {
        if (isEditing && currentEditId === id) return;

        await setupEditForm(id);
      }

      if (target.classList.contains("btn-favorito")) {
        setAppLoading(true);
        const response = await fetch(apiUrl, {
          method: "PATCH",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: id }),
        });

        if (!response.ok) throw new Error("Falha ao favoritar.");

        await fetchRecados();
      }
    } catch (error) {
      console.error("Erro na ação do card:", error);
      showError(error.message);
    } finally {
      if (!target.classList.contains("btn-editar")) {
        setAppLoading(false);
      }
    }
  };

  form.addEventListener("submit", handleFormSubmit);
  btnCancelar.addEventListener("click", resetForm);
  recadosLista.addEventListener("click", handleRecadosListClick);

  fetchRecados();
});
