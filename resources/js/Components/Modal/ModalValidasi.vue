<script setup lang="ts">
import { ref } from "vue";

import RequestAPI from "@/utils/RequestAPI";
import Toast from "@/utils/Toast";

const props = defineProps<{
    nik: any;
    temuan?: any;
}>();

const modal = ref();

const showModal = () => {
    modal.value?.showModal();
};

defineExpose({
    showModal,
});

const form = ref<{
    nik: any;
    rekomendasi: Array<{}>;
}>({
    nik: props.nik,
    rekomendasi: [],
});
const emits = defineEmits(["refresh"]);

const validasiTemuan = async () => {
    try {
        form.value.nik = props.nik;
        form.value.rekomendasi = props.temuan.rekomendasi;
        const res = await RequestAPI.validasiTemuan({ data: form.value });
        Toast.showSuccess(res.message);
        emits("refresh");
        modal.value?.close();
    } catch (e: any) {
        Toast.showError(String(e.message));
    }
};
</script>

<template>
    <dialog ref="modal" id="modalUpdateTemuan" class="modal">
        <div class="w-11/12 max-w-5xl modal-box">
            <h3 class="text-lg font-bold">Validasi Tindak Lanjut</h3>
            <p class="py-4">Pilih untuk validasi tindak lanjut</p>

            <form @submit.prevent="validasiTemuan()">
                <div
                    v-for="(rekomendasi, index) in temuan?.rekomendasi"
                    :key="index"
                    class="p-4 mb-6 border rounded-lg bg-gray-50"
                >
                    <div class="mb-4">
                        <label class="label">
                            <span class="text-lg font-bold"
                                >Rekomendasi {{ index + 1 }}</span
                            >
                        </label>
                        <div class="flex items-center">
                            <input
                                :value="rekomendasi.rekomendasi"
                                readonly
                                class="w-full input input-bordered"
                            />
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="label">
                            <span class="text-lg font-bold"
                                >Tindak Lanjut {{ index + 1 }}</span
                            >
                        </label>
                        <div class="flex items-center">
                            <input
                                :value="rekomendasi.tindak_lanjut"
                                readonly
                                :placeholder="
                                    'Isi tindak lanjut ' + (index + 1)
                                "
                                class="w-full input input-bordered"
                            />
                        </div>
                    </div>

                    <div class="mb-4">
                        <a
                            v-if="rekomendasi.bukti_url"
                            :href="rekomendasi.bukti_url"
                            type="application/pdf"
                            target="_blank"
                            class="btn"
                            ><v-icon
                                name="fa-file-pdf"
                                class="text-error"
                            />Bukti {{ index + 1 }}</a
                        >
                        <span v-else class="text-sm text-gray-500"
                            >Bukti belum tersedia</span
                        >
                    </div>

                    <div class="mb-4">
                        <label class="label">
                            <span class="text-lg font-bold">Validasi</span>
                        </label>
                        <div class="flex">
                            <div class="items-center flex-auto">
                                <input
                                    :name="'validasi' + (index + 1)"
                                    type="radio"
                                    :id="'validasi 1' + (index + 1)"
                                    value="Sesuai"
                                    v-model="rekomendasi.status"
                                    class="radio radio-success"
                                    required
                                />
                                <label
                                    :for="'validasi 1' + (index + 1)"
                                    class="ml-2 cursor-pointer"
                                    >Sesuai</label
                                >
                            </div>

                            <div class="items-center flex-auto">
                                <input
                                    :name="'validasi' + (index + 1)"
                                    type="radio"
                                    :id="'validasi 2' + (index + 1)"
                                    value="Tidak Sesuai"
                                    v-model="rekomendasi.status"
                                    class="radio radio-warning"
                                />
                                <label
                                    :for="'validasi 2' + (index + 1)"
                                    class="ml-2 cursor-pointer"
                                    >Tidak Sesuai</label
                                >
                            </div>

                            <div class="items-center flex-auto">
                                <input
                                    :name="'validasi' + (index + 1)"
                                    type="radio"
                                    :id="'validasi 3' + (index + 1)"
                                    value="Belum Ditindaklanjut"
                                    v-model="rekomendasi.status"
                                    class="radio radio-error"
                                />
                                <label
                                    :for="'validasi 3' + (index + 1)"
                                    class="ml-2 cursor-pointer"
                                    >Belum Ditindaklanjut</label
                                >
                            </div>

                            <div class="items-center flex-auto">
                                <input
                                    :name="'validasi' + (index + 1)"
                                    type="radio"
                                    :id="'validasi 4' + (index + 1)"
                                    value="Tidak Dapat Ditindaklanjut"
                                    v-model="rekomendasi.status"
                                    class="radio radio-error"
                                />
                                <label
                                    :for="'validasi 4' + (index + 1)"
                                    class="ml-2 cursor-pointer"
                                    >Tidak Dapat Ditindaklanjut</label
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Keterangan Section -->
                    <div class="mb-4">
                        <label class="label">
                            <span class="text-lg font-bold"
                                >Keterangan {{ index + 1 }}</span
                            >
                        </label>
                        <div class="flex items-center">
                            <input
                                v-model="rekomendasi.alasan"
                                :placeholder="'Isi keterangan ' + (index + 1)"
                                class="w-full input input-bordered"
                                required
                            />
                        </div>
                    </div>
                </div>

                <div class="modal-action">
                    <button type="submit" class="text-white btn btn-success">
                        <v-icon name="hi-clipboard-check" />
                        Validasi
                    </button>
                    <form method="dialog" class="z-50 modal-backdrop">
                        <button class="btn">
                            <v-icon name="hi-x" /> Close
                        </button>
                    </form>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button></button>
        </form>
    </dialog>
</template>
