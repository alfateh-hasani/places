 <section class="search container px-40 -translate-y-[50%]">
      <form
        class="grid grid-cols-5 gap-1 max-w-full py-5 pl-10 pr-5 bg-white shadow-xl rounded-full"
        id="date-range-picker"
        date-rangepicker
      >
        <div>
          <p class="font-normal text-xs text-black">Where</p>
          <select class="select2 w-full border-0 font-semibold text-sm">
            <option>Alryiadh</option>
            <option>Alryiadh</option>
            <option>Alryiadh</option>
            <option>Alryiadh</option>
          </select>
        </div>
        <div class="px-4 border-l border-blackopacity cursor-pointer">
          <p class="font-normal text-xs text-black">Check In</p>
          <input
            id="datepicker-range-start"
            name="start"
            type="text"
            class="cursor-pointer p-0 pt-1 text-black font-semibold text-sm block w-full border-0"
            placeholder="27/09/2024"
          />
        </div>
        <div class="px-4 border-l border-blackopacity cursor-pointer">
          <p class="font-normal text-xs text-black">Check Out</p>
          <input
            id="datepicker-range-end"
            name="end"
            type="text"
            class="cursor-pointer p-0 pt-1 text-black font-semibold text-sm block w-full border-0"
            placeholder="29/09/2024"
          />
        </div>
        <div
          class="px-4 border-l border-blackopacity cursor-pointer persons relative"
        >
          <p class="font-normal text-xs text-black">Who</p>
          <p class="font-semibold text-sm text-black py-1 content">Add Guest</p>
          <ul class="hidden absolute w-full bg-white p-3">
            <li class="border-b border-blackopacity pb-3 mb-3">
              <p class="inline-block w-24">Adults</p>
              <div class="inline-block">
                <div class="relative flex items-center">
                  <button
                    type="button"
                    id="decrement-button"
                    data-input-counter-decrement="counter-input"
                    class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 inline-flex items-center justify-center border border-gray-300 rounded-md h-5 w-5 focus:ring-gray-100 dark:focus:ring-gray-700 focus:ring-2 focus:outline-none"
                  >
                    <svg
                      class="w-2.5 h-2.5 text-gray-900 dark:text-white"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 18 2"
                    >
                      <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M1 1h16"
                      />
                    </svg>
                  </button>
                  <input
                    type="text"
                    id="counter-input"
                    data-input-counter
                    class="flex-shrink-0 text-black border-0 bg-transparent text-sm font-normal max-w-[2.5rem] text-center p-1"
                    placeholder=""
                    value="1"
                    required
                  />
                  <button
                    type="button"
                    id="increment-button"
                    data-input-counter-increment="counter-input"
                    class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 inline-flex items-center justify-center border border-gray-300 rounded-md h-5 w-5 focus:ring-gray-100 dark:focus:ring-gray-700 focus:ring-2 focus:outline-none"
                  >
                    <svg
                      class="w-2.5 h-2.5 text-gray-900 dark:text-white"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 18 18"
                    >
                      <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 1v16M1 9h16"
                      />
                    </svg>
                  </button>
                </div>
              </div>
            </li>
            <li>
              <p class="inline-block w-24">Childrens</p>
              <div class="inline-block">
                <div class="relative flex items-center">
                  <button
                    type="button"
                    id="decrement-button"
                    data-input-counter-decrement="counter-input1"
                    class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 inline-flex items-center justify-center border border-gray-300 rounded-md h-5 w-5 focus:ring-gray-100 dark:focus:ring-gray-700 focus:ring-2 focus:outline-none"
                  >
                    <svg
                      class="w-2.5 h-2.5 text-gray-900 dark:text-white"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 18 2"
                    >
                      <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M1 1h16"
                      />
                    </svg>
                  </button>
                  <input
                    type="text"
                    id="counter-input1"
                    data-input-counter
                    class="flex-shrink-0 text-black border-0 bg-transparent text-sm font-normal max-w-[2.5rem] text-center p-1"
                    placeholder=""
                    value="1"
                    required
                  />
                  <button
                    type="button"
                    id="increment-button"
                    data-input-counter-increment="counter-input1"
                    class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 inline-flex items-center justify-center border border-gray-300 rounded-md h-5 w-5 focus:ring-gray-100 dark:focus:ring-gray-700 focus:ring-2 focus:outline-none"
                  >
                    <svg
                      class="w-2.5 h-2.5 text-gray-900 dark:text-white"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 18 18"
                    >
                      <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 1v16M1 9h16"
                      />
                    </svg>
                  </button>
                </div>
              </div>
            </li>
          </ul>
        </div>
        <div>
          <button
            class="bg-price text-white w-full h-11 text-center rounded-full hover:bg-black ease-in-out duration-200"
          >
            <img
              class="inline-block -translate-y-0.5 mr-2"
              src="{{ asset('assets/img/search.svg')}}"
            />Search
          </button>
        </div>
      </form>
    </section>